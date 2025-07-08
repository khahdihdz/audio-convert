#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import threading
import subprocess
from typing import Optional
from kivy.app import App
from kivy.uix.boxlayout import BoxLayout
from kivy.uix.button import Button
from kivy.uix.label import Label
from kivy.uix.spinner import Spinner
from kivy.uix.progressbar import ProgressBar
from kivy.uix.textinput import TextInput
from kivy.uix.filechooser import FileChooserListView
from kivy.uix.popup import Popup
from kivy.uix.scrollview import ScrollView
from kivy.clock import Clock
from kivy.utils import platform
from kivy.logger import Logger
from kivy.storage import primary_external_storage_path
from plyer import filechooser
import json

class AudioConverterApp(App):
    """Ứng dụng chuyển đổi audio cho Android"""
    
    def __init__(self, **kwargs):
        super().__init__(**kwargs)
        self.selected_file = None
        self.output_folder = None
        self.conversion_progress = 0
        self.is_converting = False
        
        # Khởi tạo các thành phần UI
        self.progress_bar = None
        self.log_text = None
        self.convert_button = None
        self.file_label = None
        self.format_spinner = None
        self.bitrate_input = None
        
    def build(self):
        """Xây dựng giao diện ứng dụng"""
        self.title = "🎵 Audio Converter"
        
        # Layout chính
        main_layout = BoxLayout(orientation='vertical', padding=10, spacing=10)
        
        # Tiêu đề
        title = Label(
            text='🎵 CHUYỂN ĐỔI AUDIO 🎵',
            size_hint_y=None,
            height=60,
            font_size=20,
            bold=True,
            color=(0.18, 0.53, 0.67, 1)  # Màu xanh dương
        )
        main_layout.add_widget(title)
        
        # Phần chọn file
        file_section = self.create_file_section()
        main_layout.add_widget(file_section)
        
        # Phần cài đặt
        settings_section = self.create_settings_section()
        main_layout.add_widget(settings_section)
        
        # Thanh tiến trình
        self.progress_bar = ProgressBar(
            max=100,
            value=0,
            size_hint_y=None,
            height=30
        )
        main_layout.add_widget(self.progress_bar)
        
        # Nút chuyển đổi
        self.convert_button = Button(
            text='🎯 BẮT ĐẦU CHUYỂN ĐỔI',
            size_hint_y=None,
            height=60,
            font_size=16,
            bold=True,
            background_color=(0.18, 0.53, 0.67, 1)
        )
        self.convert_button.bind(on_press=self.start_conversion)
        main_layout.add_widget(self.convert_button)
        
        # Khu vực log
        log_section = self.create_log_section()
        main_layout.add_widget(log_section)
        
        # Khởi tạo log
        self.log_message("Chào mừng bạn đến với ứng dụng chuyển đổi audio!")
        self.log_message("Lưu ý: Cần cài đặt FFmpeg để sử dụng đầy đủ tính năng")
        
        return main_layout
    
    def create_file_section(self):
        """Tạo phần chọn file"""
        file_layout = BoxLayout(orientation='vertical', size_hint_y=None, height=120)
        
        # Label hiển thị file đã chọn
        self.file_label = Label(
            text='Chưa chọn file',
            size_hint_y=None,
            height=40,
            text_size=(None, None),
            halign='center'
        )
        file_layout.add_widget(self.file_label)
        
        # Nút chọn file
        select_file_btn = Button(
            text='📁 Chọn file Audio',
            size_hint_y=None,
            height=50,
            background_color=(0.64, 0.23, 0.45, 1)  # Màu tím
        )
        select_file_btn.bind(on_press=self.select_file)
        file_layout.add_widget(select_file_btn)
        
        return file_layout
    
    def create_settings_section(self):
        """Tạo phần cài đặt"""
        settings_layout = BoxLayout(orientation='vertical', size_hint_y=None, height=120)
        
        # Format selection
        format_layout = BoxLayout(orientation='horizontal', size_hint_y=None, height=50)
        format_layout.add_widget(Label(text='Định dạng:', size_hint_x=0.3))
        
        self.format_spinner = Spinner(
            text='MP3',
            values=['MP3', 'WAV', 'AAC', 'OGG', 'FLAC'],
            size_hint_x=0.7
        )
        format_layout.add_widget(self.format_spinner)
        settings_layout.add_widget(format_layout)
        
        # Bitrate input
        bitrate_layout = BoxLayout(orientation='horizontal', size_hint_y=None, height=50)
        bitrate_layout.add_widget(Label(text='Bitrate (kbps):', size_hint_x=0.3))
        
        self.bitrate_input = TextInput(
            text='192',
            multiline=False,
            input_filter='int',
            size_hint_x=0.7
        )
        bitrate_layout.add_widget(self.bitrate_input)
        settings_layout.add_widget(bitrate_layout)
        
        return settings_layout
    
    def create_log_section(self):
        """Tạo phần log"""
        log_layout = BoxLayout(orientation='vertical', size_hint_y=0.4)
        
        log_label = Label(
            text='📋 Nhật ký:',
            size_hint_y=None,
            height=30,
            bold=True
        )
        log_layout.add_widget(log_label)
        
        # ScrollView cho log
        scroll = ScrollView()
        self.log_text = TextInput(
            text='',
            multiline=True,
            readonly=True,
            background_color=(0.96, 0.96, 0.96, 1)
        )
        scroll.add_widget(self.log_text)
        log_layout.add_widget(scroll)
        
        return log_layout
    
    def select_file(self, instance):
        """Chọn file audio"""
        try:
            if platform == 'android':
                # Sử dụng filechooser cho Android
                filechooser.open_file(
                    on_selection=self.handle_file_selection,
                    filters=['*.mp3', '*.wav', '*.aac', '*.ogg', '*.flac', '*.m4a']
                )
            else:
                # Fallback cho desktop testing
                self.show_file_chooser_popup()
        except Exception as e:
            self.log_message(f"❌ Lỗi chọn file: {str(e)}")
    
    def handle_file_selection(self, selection):
        """Xử lý file đã chọn"""
        if selection:
            self.selected_file = selection[0]
            filename = os.path.basename(self.selected_file)
            self.file_label.text = f"Đã chọn: {filename}"
            self.log_message(f"📁 Đã chọn file: {filename}")
    
    def show_file_chooser_popup(self):
        """Hiển thị popup chọn file (cho desktop testing)"""
        content = BoxLayout(orientation='vertical')
        
        filechooser = FileChooserListView(
            filters=['*.mp3', '*.wav', '*.aac', '*.ogg', '*.flac', '*.m4a'],
            path=os.path.expanduser('~')
        )
        content.add_widget(filechooser)
        
        button_layout = BoxLayout(orientation='horizontal', size_hint_y=None, height=50)
        
        select_btn = Button(text='Chọn')
        cancel_btn = Button(text='Hủy')
        
        button_layout.add_widget(select_btn)
        button_layout.add_widget(cancel_btn)
        content.add_widget(button_layout)
        
        popup = Popup(
            title='Chọn file Audio',
            content=content,
            size_hint=(0.9, 0.9)
        )
        
        def on_select(instance):
            if filechooser.selection:
                self.handle_file_selection(filechooser.selection)
            popup.dismiss()
        
        def on_cancel(instance):
            popup.dismiss()
        
        select_btn.bind(on_press=on_select)
        cancel_btn.bind(on_press=on_cancel)
        
        popup.open()
    
    def start_conversion(self, instance):
        """Bắt đầu chuyển đổi"""
        if not self.selected_file:
            self.log_message("❌ Vui lòng chọn file đầu vào!")
            return
        
        if self.is_converting:
            self.log_message("⚠️ Đang chuyển đổi, vui lòng đợi!")
            return
        
        # Thiết lập output path
        if platform == 'android':
            self.output_folder = primary_external_storage_path()
        else:
            self.output_folder = os.path.expanduser('~/Downloads')
        
        # Tạo tên file đầu ra
        input_name = os.path.splitext(os.path.basename(self.selected_file))[0]
        output_format = self.format_spinner.text.lower()
        output_file = os.path.join(self.output_folder, f"{input_name}_converted.{output_format}")
        
        # Bắt đầu chuyển đổi trong thread riêng
        self.is_converting = True
        self.convert_button.text = '🔄 Đang chuyển đổi...'
        self.convert_button.disabled = True
        
        conversion_thread = threading.Thread(
            target=self.convert_audio,
            args=(self.selected_file, output_file, output_format)
        )
        conversion_thread.daemon = True
        conversion_thread.start()
        
        self.log_message(f"🚀 Bắt đầu chuyển đổi sang {output_format.upper()}...")
    
    def convert_audio(self, input_file, output_file, output_format):
        """Chuyển đổi audio (chạy trong thread riêng)"""
        try:
            # Kiểm tra FFmpeg
            if not self.check_ffmpeg():
                Clock.schedule_once(lambda dt: self.conversion_finished(
                    False, "❌ Không tìm thấy FFmpeg. Vui lòng cài đặt FFmpeg."
                ), 0)
                return
            
            # Tạo lệnh FFmpeg
            bitrate = self.bitrate_input.text or '192'
            cmd = [
                'ffmpeg',
                '-i', input_file,
                '-ab', f'{bitrate}k',
                '-y',
                output_file
            ]
            
            # Thêm codec cho từng định dạng
            if output_format == 'mp3':
                cmd.insert(-1, '-acodec')
                cmd.insert(-1, 'libmp3lame')
            elif output_format == 'aac':
                cmd.insert(-1, '-acodec')
                cmd.insert(-1, 'aac')
            elif output_format == 'ogg':
                cmd.insert(-1, '-acodec')
                cmd.insert(-1, 'libvorbis')
            
            # Mô phỏng tiến trình
            for i in range(0, 101, 5):
                Clock.schedule_once(lambda dt, progress=i: self.update_progress(progress), 0)
                threading.Event().wait(0.1)
            
            # Chạy FFmpeg
            process = subprocess.Popen(
                cmd,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                universal_newlines=True
            )
            
            stdout, stderr = process.communicate()
            
            if process.returncode == 0:
                Clock.schedule_once(lambda dt: self.conversion_finished(
                    True, f"✅ Chuyển đổi thành công! File lưu tại: {output_file}"
                ), 0)
            else:
                Clock.schedule_once(lambda dt: self.conversion_finished(
                    False, f"❌ Lỗi chuyển đổi: {stderr}"
                ), 0)
                
        except Exception as e:
            Clock.schedule_once(lambda dt: self.conversion_finished(
                False, f"❌ Lỗi: {str(e)}"
            ), 0)
    
    def check_ffmpeg(self):
        """Kiểm tra FFmpeg có sẵn không"""
        try:
            subprocess.run(['ffmpeg', '-version'], 
                         capture_output=True, check=True)
            return True
        except (subprocess.CalledProcessError, FileNotFoundError):
            return False
    
    def update_progress(self, value):
        """Cập nhật thanh tiến trình"""
        self.progress_bar.value = value
    
    def conversion_finished(self, success, message):
        """Xử lý khi chuyển đổi hoàn thành"""
        self.is_converting = False
        self.convert_button.text = '🎯 BẮT ĐẦU CHUYỂN ĐỔI'
        self.convert_button.disabled = False
        
        if success:
            self.progress_bar.value = 100
        else:
            self.progress_bar.value = 0
        
        self.log_message(message)
    
    def log_message(self, message):
        """Thêm tin nhắn vào log"""
        if self.log_text:
            self.log_text.text += f"\n{message}"
            # Scroll to bottom
            self.log_text.cursor = (0, 0)

# Chạy ứng dụng
if __name__ == '__main__':
    AudioConverterApp().run()
