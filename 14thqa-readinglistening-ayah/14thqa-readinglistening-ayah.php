<?php
/*
 Plugin Name: 14thQA-ReadingListening-Ayah
 Plugin URI: https://www.14thquranacademy.com
 Description: Play Quran verses with custom reciters, optional translations, and Arabic text range via shortcode.
 Version: 1.3.9.1 // Incremented to force reload
 Author: Syed Tabassum Raza
 Author URI: https://www.14thquranacademy.com
 License: GPL-2.0+
 Requires at least: 6.0
 Tested up to: 6.7
 Requires PHP: 7.4
 Text Domain: 14thqa-readinglistening-ayah
*/

if (!defined('ABSPATH')) {
    exit;
}

class QuranAudioPlayer {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_quran_audio_get_translation', [$this, 'ajax_get_translation']);
        add_action('wp_ajax_quran_audio_get_arabic_range', [$this, 'ajax_get_arabic_range']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('14thqa-readinglistening-ayah-css', plugins_url('css/player.css', __FILE__), [], '1.3.9.1');
        wp_enqueue_style('amiri-font', 'https://fonts.googleapis.com/css2?family=Amiri&display=swap', [], null);
        wp_enqueue_script('14thqa-readinglistening-ayah-js', plugins_url('js/player.js', __FILE__), ['jquery'], '1.3.9.1', true);

        $surahs = [];
        $transient = get_transient('quran_audio_surahs');
        if ($transient === false) {
            $response = wp_remote_get('https://api.alquran.cloud/v1/surah', ['timeout' => 10]);
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body);
                if ($data && isset($data->data)) {
                    foreach ($data->data as $surah) {
                        $surahs[$surah->number] = [
                            'name' => $surah->englishName . ' (' . $surah->name . ')',
                            'verses' => $surah->numberOfAyahs
                        ];
                    }
                    set_transient('quran_audio_surahs', $surahs, WEEK_IN_SECONDS);
                }
            }
        } else {
            $surahs = $transient;
        }

        wp_localize_script('14thqa-readinglistening-ayah-js', 'quranAudio', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('quran-audio-nonce'),
            'is_rtl' => is_rtl() ? 'yes' : 'no',
            'surahs' => $surahs ?: $this->fallback_surahs()
        ]);
    }

    private function fallback_surahs() {
        $fallback = [];
        for ($i = 1; $i <= 114; $i++) {
            $fallback[$i] = ['name' => sprintf(__('Sura %d'), $i), 'verses' => 286];
        }
        return $fallback;
    }

    public function register_shortcodes() {
        require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
    }

    public function register_settings_page() {
        add_options_page(
            __('14thQA Reading & Listening Settings', '14thqa-readinglistening-ayah'),
            __('14thQA Reading & Listening', '14thqa-readinglistening-ayah'),
            'manage_options',
            '14thqa-readinglistening-ayah',
            [$this, 'settings_page_content']
        );
    }

    public function register_settings() {
        register_setting('quran_audio_settings_group', 'quran_audio_default_reciter', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('quran_audio_settings_group', 'quran_audio_default_translator', ['sanitize_callback' => 'sanitize_text_field']);
    }

    public function settings_page_content() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
        quran_audio_settings_page();
    }

    public static function get_translation($sura, $verse, $edition) {
        $response = wp_remote_get("https://api.alquran.cloud/v1/ayah/{$sura}:{$verse}/{$edition}", [
            'timeout' => 10,
            'sslverify' => true
        ]);
        if (is_wp_error($response)) {
            error_log('Translation API error: ' . $response->get_error_message());
            return 'Translation unavailable (API error)';
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        return $data->data->text ?? 'Translation unavailable (no data)';
    }

    public static function get_arabic_range($sura, $start_verse, $end_verse) {
        $response = wp_remote_get("https://api.alquran.cloud/v1/surah/{$sura}/ar", [
            'timeout' => 10,
            'sslverify' => true
        ]);
        if (is_wp_error($response)) {
            error_log('Arabic range API error: ' . $response->get_error_message());
            return 'Arabic text unavailable (API error)';
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        if (!$data || !isset($data->data->ayahs)) {
            return 'Arabic text unavailable (no data)';
        }

        $verses = [];
        foreach ($data->data->ayahs as $ayah) {
            $verse_number = $ayah->numberInSurah;
            if ($verse_number >= $start_verse && $verse_number <= $end_verse) {
                $verses[$verse_number] = $ayah->text;
            }
        }
        return $verses;
    }

    public function ajax_get_translation() {
        check_ajax_referer('quran-audio-nonce', 'nonce');
        $sura = isset($_POST['sura']) ? absint($_POST['sura']) : 1;
        $verse = isset($_POST['verse']) ? absint($_POST['verse']) : 1;
        $translator = isset($_POST['translator']) ? sanitize_text_field($_POST['translator']) : 'en.sahih';
        $translation = self::get_translation($sura, $verse, $translator);
        wp_send_json_success($translation);
    }

    public function ajax_get_arabic_range() {
        check_ajax_referer('quran-audio-nonce', 'nonce');
        $sura = isset($_POST['sura']) ? absint($_POST['sura']) : 1;
        $start_verse = isset($_POST['start_verse']) ? absint($_POST['start_verse']) : 1;
        $end_verse = isset($_POST['end_verse']) ? absint($_POST['end_verse']) : 7;
        $arabic_range = self::get_arabic_range($sura, $start_verse, $end_verse);
        wp_send_json_success($arabic_range);
    }
}

new QuranAudioPlayer();