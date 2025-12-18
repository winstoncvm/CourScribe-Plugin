/**
 * Path: courscribe/assets/js/courscribe/dictation/input-field-dictation.js
 */
jQuery(document).ready(function ($) {

    class SpeechInput {
        constructor(wrapper, moduleId, inputClasses, inputValue, inputId, inputName) {
            this.wrapper = wrapper;
            this.moduleId = moduleId;
            this.inputClasses = inputClasses || 'form-control bg-dark text-light';
            this.inputValue = inputValue || '';
            this.inputId = inputId || `speech-input-${this.moduleId}`;
            this.inputName = inputName || `speech-input-${this.moduleId}`; // Use data-name from wrapper
            this.isListening = false;
            this.recognition = this.initRecognition();
            this.render();
            this.bindEvents();
        }

        initRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition || window.mozSpeechRecognition || window.msSpeechRecognition;
            if (!SpeechRecognition) {
                console.error('Speech recognition not supported.');
                return null;
            }

            const recognition = new SpeechRecognition();
            recognition.lang = 'en-US';
            recognition.continuous = false;
            recognition.interimResults = false;

            recognition.onstart = () => {
                this.isListening = true;
                this.button.classList.add('listening');
                this.button.innerHTML = '<i class="fa fa-stop"></i>';
                this.input.placeholder = 'Listening...';
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                this.handleTranscript(transcript);
            };

            recognition.onend = () => {
                this.isListening = false;
                this.button.classList.remove('listening');
                this.button.innerHTML = '<i class="fa fa-microphone"></i>';
                this.input.placeholder = 'Start speaking...';
            };

            recognition.onerror = (event) => {
                console.error('Speech recognition error:', event.error);
                if (event.error === 'not-allowed') {
                    alert('Microphone access denied. Please allow it in your browser settings.');
                }
                recognition.onend();
            };

            return recognition;
        }

        render() {
            if (!this.recognition) {
                this.wrapper.innerHTML = `<input type="text" class="speech-input ${this.inputClasses}" placeholder="Speech not supported" disabled>`;
                return;
            }

            this.wrapper.innerHTML = `
                <div class="speech-to-text-container">
                    <input type="text" id="${this.inputId}" name="${this.inputName}" class="speech-input ${this.inputClasses}" value="${this.inputValue}" placeholder="Start speaking..." />
                    <div class="courscribe-tooltip" 
                        data-title="Speech-to-Text"
                        data-description="Use your microphone to dictate text. Requires CourScribe Pro."
                        data-required-package="CourScribe Pro (Agency)">
                        <button id="start-speech-btn-${this.moduleId}" class="speech-btn">
                            <i class="fa fa-microphone"></i>
                        </button>
                    </div>
                </div>`;
            this.input = this.wrapper.querySelector(`#${this.inputId}`);
            this.button = this.wrapper.querySelector(`#start-speech-btn-${this.moduleId}`);
        }

        handleTranscript(transcript) {
            const currentText = this.input.value.trim();
            if (currentText.length > 0) {
                const userChoice = confirm('Text already exists. Click "OK" to append, or "Cancel" to replace.');
                if (userChoice) {
                    this.input.value = currentText + ' ' + transcript;
                } else {
                    this.input.value = transcript;
                }
            } else {
                this.input.value = transcript;
            }
        }

        bindEvents() {
            if (!this.recognition) return;

            this.button.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.isListening) {
                    this.recognition.stop();
                } else {
                    try {
                        this.recognition.start();
                    } catch (error) {
                        console.error('Error starting recognition:', error);
                        if (error.message.includes('permission')) {
                            alert('Microphone access is required. Please allow it in your browser settings.');
                        }
                    }
                }
            });

            this.input.addEventListener('input', () => {
                if (!this.isListening) {
                    this.button.classList.remove('listening');
                    this.button.innerHTML = '<i class="fa fa-microphone"></i>';
                }
            });
        }
    }

    $('.speech-input-wrapper').each(function () {
        const moduleId = $(this).data('module-id');
        const inputClasses = $(this).data('classes');
        const inputValue = $(this).data('value') || '';
        const inputId = $(this).data('id');
        const inputName = $(this).data('name'); // Get name from data-name
        new SpeechInput(this, moduleId, inputClasses, inputValue, inputId, inputName);
    });
});