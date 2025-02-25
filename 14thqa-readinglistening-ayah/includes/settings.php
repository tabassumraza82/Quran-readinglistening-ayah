<?php
if (!defined('ABSPATH')) {
    exit;
}

function quran_audio_settings_page() {
    $reciters = [
        'alafasy' => 'Mishary Rashid Alafasy',
        'husary' => 'Mahmoud Khalil Al-Husary',
        'minshawi' => 'Mohamed Siddiq Al-Minshawi',
        'abdulbasit' => 'Abdul Basit Abdus Samad'
    ];
    $translators = [
        'en.sahih' => 'Sahih International (English)',
        'ar' => 'Arabic',
        'fr.montada' => 'French (Montada)',
        'ur.jalandhry' => 'Urdu (Jalandhry)'
    ];

    // Fetch Surah list from transient or API
    $surahs = get_transient('quran_audio_surahs');
    if ($surahs === false) {
        $surahs = [];
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
    }
    ?>
    <div class="wrap quran-audio-settings">
        <h1><?php _e('14thQA Reading & Listening Settings', '14thqa-readinglistening-ayah'); ?></h1>
        <?php if (isset($_GET['settings-updated'])) : ?>
            <div id="message" class="updated notice is-dismissible">
                <p><?php _e('Settings saved successfully.', '14thqa-readinglistening-ayah'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Existing Default Settings -->
        <form method="post" action="options.php">
            <?php
            settings_fields('quran_audio_settings_group');
            do_settings_sections('quran_audio_settings_group');
            ?>
            <h2><?php _e('Default Settings', '14thqa-readinglistening-ayah'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="quran_audio_default_reciter"><?php _e('Default Reciter', '14thqa-readinglistening-ayah'); ?></label>
                    </th>
                    <td>
                        <select name="quran_audio_default_reciter" id="quran_audio_default_reciter">
                            <?php foreach ($reciters as $key => $name) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected(get_option('quran_audio_default_reciter', 'alafasy'), $key); ?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Choose the default reciter for audio playback.', '14thqa-readinglistening-ayah'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="quran_audio_default_translator"><?php _e('Default Translator', '14thqa-readinglistening-ayah'); ?></label>
                    </th>
                    <td>
                        <select name="quran_audio_default_translator" id="quran_audio_default_translator">
                            <?php foreach ($translators as $key => $name) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected(get_option('quran_audio_default_translator', 'en.sahih'), $key); ?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Choose the default translation language.', '14thqa-readinglistening-ayah'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Default Settings', '14thqa-readinglistening-ayah')); ?>
        </form>

        <!-- Shortcode Generator -->
        <h2><?php _e('Shortcode Generator', '14thqa-readinglistening-ayah'); ?></h2>
        <div class="shortcode-generator">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="shortcode_reciter"><?php _e('Reciter', '14thqa-readinglistening-ayah'); ?></label>
                    </th>
                    <td>
                        <select id="shortcode_reciter">
                            <?php foreach ($reciters as $key => $name) : ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="shortcode_translator"><?php _e('Translator', '14thqa-readinglistening-ayah'); ?></label>
                    </th>
                    <td>
                        <select id="shortcode_translator">
                            <?php foreach ($translators as $key => $name) : ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="shortcode_sura"><?php _e('Surah', '14thqa-readinglistening-ayah'); ?></label>
                    </th>
                    <td>
                        <select id="shortcode_sura">
                            <?php foreach ($surahs as $number => $data) : ?>
                                <option value="<?php echo esc_attr($number); ?>" data-max-verses="<?php echo esc_attr($data['verses']); ?>"><?php echo esc_html($data['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="shortcode_from_ayah"><?php _e('From Ayah', '14thqa-readinglistening-ayah'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="shortcode_from_ayah" min="1" value="1">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="shortcode_to_ayah"><?php _e('To Ayah', '14thqa-readinglistening-ayah'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="shortcode_to_ayah" min="1" value="7">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="shortcode_show_translation"><?php _e('Show Translation', '14thqa-readinglistening-ayah'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="shortcode_show_translation">
                    </td>
                </tr>
            </table>
            <p><strong><?php _e('Generated Shortcode:', '14thqa-readinglistening-ayah'); ?></strong></p>
            <textarea id="shortcode_output" readonly rows="3" style="width: 100%;"></textarea>
            <button id="copy_shortcode" class="button"><?php _e('Copy to Clipboard', '14thqa-readinglistening-ayah'); ?></button>
        </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function updateShortcode() {
                const reciter = $('#shortcode_reciter').val();
                const translator = $('#shortcode_translator').val();
                const sura = $('#shortcode_sura').val();
                const fromAyah = parseInt($('#shortcode_from_ayah').val()) || 1;
                const toAyah = parseInt($('#shortcode_to_ayah').val()) || 7;
                const showTranslation = $('#shortcode_show_translation').is(':checked');
                const maxVerses = parseInt($('#shortcode_sura option:selected').data('max-verses')) || 286;

                // Validate ayah range
                const validFromAyah = Math.max(1, Math.min(fromAyah, maxVerses));
                const validToAyah = Math.max(validFromAyah, Math.min(toAyah, maxVerses));
                $('#shortcode_from_ayah').val(validFromAyah);
                $('#shortcode_to_ayah').val(validToAyah);

                let shortcode = `[quran_audio sura="${sura}" start_verse="${validFromAyah}" end_verse="${validToAyah}" reciter="${reciter}" translator="${translator}"`;
                if (showTranslation) {
                    shortcode += ' show_translation="true"';
                }
                shortcode += ']';
                $('#shortcode_output').val(shortcode);
            }

            // Update "To Ayah" when Surah changes
            $('#shortcode_sura').on('change', function() {
                const maxVerses = parseInt($(this).find('option:selected').data('max-verses')) || 286;
                $('#shortcode_to_ayah').val(maxVerses).attr('max', maxVerses);
                updateShortcode();
            });

            // Update shortcode on other input changes
            $('#shortcode_reciter, #shortcode_translator, #shortcode_from_ayah, #shortcode_to_ayah, #shortcode_show_translation').on('change input', updateShortcode);

            // Copy to clipboard
            $('#copy_shortcode').on('click', function() {
                const $textarea = $('#shortcode_output');
                $textarea.select();
                document.execCommand('copy');
                alert('<?php _e('Shortcode copied to clipboard!', '14thqa-readinglistening-ayah'); ?>');
            });

            // Initial update with default Surah's max verses
            const initialMaxVerses = parseInt($('#shortcode_sura option:selected').data('max-verses')) || 286;
            $('#shortcode_to_ayah').val(initialMaxVerses).attr('max', initialMaxVerses);
            updateShortcode();
        });
    </script>
    <?php
}