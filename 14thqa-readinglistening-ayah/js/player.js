jQuery(document).ready(function($) {
    $('.quran-audio-player').each(function() {
        const $player = $(this);
        const $audio = $player.find('.quran-audio');
        const $currentVerse = $player.find('.current-verse');
        const $translation = $player.find('.translation');
        const $quranText = $player.find('.quran-text');
        const $verses = $quranText.find('.verse');
        const $title = $player.find('.player-title');
        const $autonextToggle = $player.find('.autonext-toggle');
        const $repeatToggle = $player.find('.repeat-toggle');
        const $progressCircle = $repeatToggle.find('.progress-circle');
        const $durationText = $repeatToggle.find('.duration');
        const $prevVerse = $player.find('.prev-verse');
        const $nextVerse = $player.find('.next-verse');

        let sura = parseInt($player.data('sura'));
        let startVerse = parseInt($player.data('start-verse'));
        let endVerse = parseInt($player.data('end-verse'));
        const reciter = $player.data('reciter');
        const translator = $player.data('translator');
        const repeat = parseInt($player.data('repeat'));
        const showTranslation = $player.data('show-translation') === 'true';
        let currentVerse = startVerse;
        let autonext = true;
        let repeatInterval = false;
        let verseDuration = 0;
        let countdownInterval = null;

        console.log('Initializing player for sura: ' + sura + ', start: ' + startVerse + ', end: ' + endVerse);
        console.log('Show translation: ' + showTranslation);
        console.log('Autonext initial state: ' + autonext);
        console.log('Repeat interval initial state: ' + repeatInterval);

        function pad(num) {
            return num.toString().padStart(3, '0');
        }

        function updateButtonStates() {
            $prevVerse.toggleClass('disabled', currentVerse <= startVerse);
            $nextVerse.toggleClass('disabled', currentVerse >= endVerse);
        }

        function loadVerse(verse, play = false) {
            console.log(`Loading verse: ${sura}:${verse}, play: ${play}`);

            const reciterMap = {
                'alafasy': 'Alafasy_128kbps',
                'husary': 'Husary_128kbps',
                'minshawi': 'Minshawi_Mujawwad_192kbps',
                'abdulbasit': 'Abdul_Basit_Murattal_192kbps'
            };
            const reciterPath = reciterMap[reciter] || reciterMap['alafasy'];
            const url = `https://everyayah.com/data/${reciterPath}/${pad(sura)}${pad(verse)}.mp3`;
            console.log(`Audio URL: ${url}`);
            $audio.attr('src', url);
            $currentVerse.text(verse);
            currentVerse = parseInt(verse); // Update currentVerse

            $audio.off('error').on('error', function() {
                console.log('Audio failed to load: ' + url);
                $title.text('Audio unavailable - check console');
            });

            const loadMetadata = new Promise((resolve) => {
                $audio.off('loadedmetadata').on('loadedmetadata', function() {
                    verseDuration = $audio[0].duration || 5; // Fallback to 5s
                    console.log('Verse duration loaded: ' + verseDuration + ' seconds');
                    resolve();
                });
            });

            if (play) {
                loadMetadata.then(() => {
                    $audio[0].play().catch(function(e) {
                        console.log('Playback error: ' + e.message + ' - URL: ' + url);
                        $title.text('Audio playback failed: ' + e.message);
                    });
                });
            } else {
                $audio[0].pause();
                $audio[0].currentTime = 0;
                $title.text('Click Next to start audio');
            }

            $quranText.find('.verse').removeClass('current');
            $quranText.find(`.verse[data-verse="${verse}"]`).addClass('current');

            updateButtonStates();

            // Always update translation when verse changes
            if (showTranslation) {
                console.log(`Fetching translation for ${sura}:${verse}`);
                $translation.text('Loading translation...');
                $.ajax({
                    url: quranAudio.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'quran_audio_get_translation',
                        sura: sura,
                        verse: verse,
                        translator: translator,
                        nonce: quranAudio.nonce
                    },
                    success: function(response) {
                        console.log('Translation received: ' + response.data);
                        $translation.text(response.data || 'No translation available');
                        $translation.addClass('current');
                    },
                    error: function(xhr, status, error) {
                        console.log('Translation fetch failed: ' + status + ' - ' + error);
                        $translation.text('Translation unavailable');
                        $translation.removeClass('current');
                    }
                });
            }
        }

        $audio.on('ended', function() {
            console.log('Audio ended for verse: ' + currentVerse + ', repeatInterval: ' + repeatInterval + ', autonext: ' + autonext + ', verseDuration: ' + verseDuration);
            let repeats = $audio.data('repeat-count') || 0;
            if (repeats < repeat - 1) {
                repeats++;
                $audio.data('repeat-count', repeats);
                $audio[0].play();
            } else {
                $audio.data('repeat-count', 0);
                if (currentVerse < endVerse && autonext) {
                    if (repeatInterval && verseDuration > 0) {
                        console.log('Adding silent interval of ' + verseDuration + ' seconds');
                        $progressCircle.addClass('active').css('--duration', verseDuration + 's');
                        let remainingTime = verseDuration;
                        $durationText.text(remainingTime.toFixed(1));
                        countdownInterval = setInterval(() => {
                            remainingTime -= 0.1;
                            if (remainingTime <= 0) {
                                clearInterval(countdownInterval);
                                countdownInterval = null;
                                $progressCircle.removeClass('active');
                                $durationText.text('');
                            } else {
                                $durationText.text(remainingTime.toFixed(1));
                            }
                        }, 100); // Update every 100ms
                        setTimeout(function() {
                            if (countdownInterval) {
                                clearInterval(countdownInterval);
                                countdownInterval = null;
                            }
                            $progressCircle.removeClass('active');
                            $durationText.text('');
                            currentVerse++;
                            loadVerse(currentVerse, true);
                        }, verseDuration * 1000);
                    } else {
                        currentVerse++;
                        loadVerse(currentVerse, true);
                    }
                } else {
                    $title.text('Click Next to continue');
                }
            }
        });

        $prevVerse.on('click', function() {
            console.log('Previous clicked');
            if (currentVerse > startVerse) {
                currentVerse--;
                loadVerse(currentVerse, true);
            }
        });

        $nextVerse.on('click', function() {
            console.log('Next clicked');
            if (currentVerse < endVerse) {
                currentVerse++;
                loadVerse(currentVerse, true);
            }
        });

        $autonextToggle.on('click', function() {
            console.log('Autonext toggle clicked');
            autonext = !autonext;
            $(this).attr('data-autonext', autonext ? 'on' : 'off');
            $(this).text(autonext ? 'Recite All' : 'Recite One');
            console.log('Button text set to: ' + (autonext ? 'Recite All' : 'Recite One'));
            console.log('Autonext set to: ' + autonext);
        });

        $verses.on('click', function() {
            const verseNum = parseInt($(this).data('verse'));
            console.log('Verse clicked: ' + verseNum);
            if (verseNum >= startVerse && verseNum <= endVerse) {
                loadVerse(verseNum, true);
            }
        });

        window.toggleRepeatInterval = function(element, playerId) {
            console.log('Repeat interval toggle clicked for player: ' + playerId);
            const player = document.getElementById('quran-player-' + playerId);
            if (player) {
                player.repeatInterval = element.checked;
                console.log('Repeat interval set to: ' + player.repeatInterval + ' for player: ' + playerId);
                repeatInterval = player.repeatInterval;
            } else {
                console.error('Player not found with ID: quran-player-' + playerId);
            }
        };

        // Initial load without playing
        console.log('Initial load for verse: ' + currentVerse);
        $quranText.find(`.verse[data-verse="${currentVerse}"]`).addClass('current');
        loadVerse(currentVerse, false);

        $player[0].repeatInterval = repeatInterval;
    });
});