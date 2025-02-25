<?php
if (!defined('ABSPATH')) {
    exit;
}

function quran_audio_shortcode($atts) {
    $atts = shortcode_atts([
        'sura' => 1,
        'start_verse' => 1,
        'end_verse' => 7,
        'reciter' => get_option('quran_audio_default_reciter', 'alafasy'),
        'translator' => get_option('quran_audio_default_translator', 'en.sahih'),
        'repeat' => get_option('quran_audio_default_repeat', 1),
        'show_translation' => get_option('quran_audio_show_translation', 0) ? 'true' : 'false'
    ], $atts, 'quran_audio');

    $sura = absint($atts['sura']);
    $start_verse = absint($atts['start_verse']);
    $end_verse = absint($atts['end_verse']);
    $reciter = sanitize_text_field($atts['reciter']);
    $translator = sanitize_text_field($atts['translator']);
    $repeat = absint($atts['repeat']);
    $show_translation = filter_var($atts['show_translation'], FILTER_VALIDATE_BOOLEAN);

    $sura = max(1, min(114, $sura));
    $start_verse = max(1, $start_verse);
    $end_verse = max($start_verse, $end_verse);

    $reciters = [
        'alafasy' => ['name' => 'Mishary Rashid Alafasy'],
        'husary' => ['name' => 'Mahmoud Khalil Al-Husary'],
        'minshawi' => ['name' => 'Mohamed Siddiq Al-Minshawi'],
        'abdulbasit' => ['name' => 'Abdul Basit Abdus Samad']
    ];
    $reciter_key = array_key_exists($reciter, $reciters) ? $reciter : get_option('quran_audio_default_reciter', 'alafasy');

    $translators = [
        'en.sahih' => 'Sahih International (English)',
        'ar' => 'Arabic',
        'fr.montada' => 'French (Montada)',
        'ur.jalandhry' => 'Urdu (Jalandhry)'
    ];
    $translator_key = array_key_exists($translator, $translators) ? $translator : get_option('quran_audio_default_translator', 'en.sahih');

    $initial_translation = $show_translation ? QuranAudioPlayer::get_translation($sura, $start_verse, $translator_key) : '';
    $arabic_range = QuranAudioPlayer::get_arabic_range($sura, $start_verse, $end_verse);
    $rand_id = rand(1000, 9999); // Unique ID for each player instance

    $output = '<div class="quran-audio-player" id="quran-player-' . esc_attr($rand_id) . '" data-sura="' . esc_attr($sura) . '" data-start-verse="' . esc_attr($start_verse) . '" data-end-verse="' . esc_attr($end_verse) . '" data-reciter="' . esc_attr($reciter_key) . '" data-translator="' . esc_attr($translator_key) . '" data-repeat="' . esc_attr($repeat) . '" data-show-translation="' . esc_attr($show_translation ? 'true' : 'false') . '">';
    $output .= '<div class="quran-text-block">';
    $output .= '<div class="quran-text">';
    if (is_array($arabic_range)) {
        foreach ($arabic_range as $verse_num => $text) {
            $output .= '<span class="verse" data-verse="' . esc_attr($verse_num) . '">' . esc_html($text) . ' (' . esc_html($verse_num) . ')</span> ';
        }
    } else {
        $output .= esc_html($arabic_range);
    }
    $output .= '</div>';
    if ($show_translation) {
        $output .= '<div class="translation">' . esc_html($initial_translation) . '</div>';
    }
    $output .= '</div>';
    $output .= '<p class="player-title">' . esc_html(sprintf(__('Sura %d, Verses %d-%d by %s'), $sura, $start_verse, $end_verse, $reciters[$reciter_key]['name'])) . '</p>';
    $output .= '<audio controls class="quran-audio"></audio>';
    $output .= '<div class="verse-controls">';
    $output .= '<button class="prev-verse">' . esc_html__('Previous') . '</button>';
    $output .= '<span class="current-verse">' . esc_html($start_verse) . '</span>';
    $output .= '<button class="next-verse">' . esc_html__('Next') . '</button>';
    $output .= '<button class="autonext-toggle" data-autonext="on">' . esc_html('Recite All') . '</button>';
    $output .= '<label class="repeat-toggle"><input type="checkbox" id="repeat-interval-toggle-' . esc_attr($rand_id) . '" onchange="toggleRepeatInterval(this, \'' . esc_attr($rand_id) . '\')"> ' . esc_html__('I will repeat too') . ' <span class="progress-circle"></span><span class="duration"></span></label>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}
add_shortcode('quran_audio', 'quran_audio_shortcode');