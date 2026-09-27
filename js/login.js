document.addEventListener('DOMContentLoaded', () => {
    const cardShell = document.getElementById('cardShell');
    const formContent = document.getElementById('formContent');
    const transitionGlyph = document.getElementById('transitionGlyph');
    
    // Templates
    const tplSignIn = document.getElementById('tpl-signin');
    const tplSignUp = document.getElementById('tpl-signup');
    
    // State
    let isAnimating = false;
    
    // Initialize with Sign In view
    loadView('signin', true);
    
    function loadView(viewType, animate = true) {
        if (isAnimating) return;
        
        if (!animate) {
            injectTemplate(viewType);
            return;
        }
        
        isAnimating = true;
        cardShell.style.pointerEvents = 'none';
        
        // 1. Current text fades out
        formContent.classList.remove('fade-in');
        formContent.classList.add('is-leaving');
        
        // Outer boxes begin moving closer to each other towards center
        cardShell.classList.add('is-transitioning');
        
        // After text fade-out completes (250ms)
        setTimeout(() => {
            // Empty the form content and prepare the invisible entering state
            formContent.innerHTML = '';
            formContent.classList.remove('is-leaving');
            formContent.classList.add('is-entering');
            
            // Outer boxes reach the center at 500ms (250ms after fade-out)
            setTimeout(() => {
                // 2. Outer boxes meet in the center - animate them (pulse & glow)
                cardShell.classList.add('is-pulsing');
                
                // Inject new template while it is completely hidden (is-entering has opacity: 0)
                injectTemplate(viewType);
                
                // Pulse duration is 500ms
                setTimeout(() => {
                    cardShell.classList.remove('is-pulsing');
                    
                    // 3. Now the opposing outer boxes expand back out towards the corners
                    cardShell.classList.remove('is-transitioning');
                    
                    // 4. Then, only when the opposing outer box expands do the texts appear
                    setTimeout(() => {
                        // Force reflow so browser catches the transition from opacity 0 to 1
                        void formContent.offsetWidth;
                        
                        formContent.classList.remove('is-entering');
                        formContent.classList.add('fade-in');
                        
                        // Primary button light sweep effect
                        const primaryBtn = formContent.querySelector('.primary-btn');
                        if (primaryBtn) {
                            setTimeout(() => {
                                primaryBtn.classList.add('sweep-animate');
                            }, 300);
                            setTimeout(() => {
                                primaryBtn.classList.remove('sweep-animate');
                            }, 900);
                        }
                        
                        // Finish transition after fade-in completes
                        setTimeout(() => {
                            formContent.classList.remove('fade-in');
                            cardShell.style.pointerEvents = 'auto';
                            isAnimating = false;
                        }, 550);
                        
                    }, 50); // Bracket expansion is underway; text begins fading in gracefully
                    
                }, 500); // Center pulse duration
                
            }, 250); // Wait for brackets to reach center
            
        }, 250); // Wait for text fade-out
    }
    
    function injectTemplate(viewType) {
        const template = viewType === 'signin' ? tplSignIn : tplSignUp;
        const clone = document.importNode(template.content, true);
        
        formContent.appendChild(clone);
        bindEvents();
    }
    
    function bindEvents() {
        // Toggle view buttons
        const toggleBtns = formContent.querySelectorAll('.toggle-view-btn');
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetView = btn.getAttribute('data-target');
                loadView(targetView);
            });
        });
        
        // Password toggle
        const togglePasswordBtns = formContent.querySelectorAll('.toggle-password');
        togglePasswordBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                const iconOff = btn.querySelector('.eye-off');
                const iconOn = btn.querySelector('.eye');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    iconOff.style.display = 'none';
                    iconOn.style.display = 'block';
                } else {
                    input.type = 'password';
                    iconOff.style.display = 'block';
                    iconOn.style.display = 'none';
                }
            });
        });

        // Form Validation Stubs for Sign Up
        const signUpForm = document.getElementById('signUpForm');
        if (signUpForm) {
            signUpForm.addEventListener('submit', (e) => {
                const pass = signUpForm.querySelector('input[name="password"]');
                const confirmPass = signUpForm.querySelector('input[name="confirm_password"]');
                const errorDiv = document.getElementById('signupError');
                
                if (pass && confirmPass && pass.value !== confirmPass.value) {
                    e.preventDefault();
                    errorDiv.textContent = 'Passwords do not match.';
                    errorDiv.style.display = 'block';
                } else {
                    errorDiv.style.display = 'none';
                    // Allow normal submission or handle via AJAX
                    // e.preventDefault(); console.log("Sign up valid");
                }
            });
        }
    }
});
