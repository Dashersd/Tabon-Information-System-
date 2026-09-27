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
    loadView('signin', false);
    
    function loadView(viewType, animate = true) {
        if (isAnimating) return;
        
        if (!animate) {
            injectTemplate(viewType);
            return;
        }
        
        isAnimating = true;
        
        // Lock explicit height before leaving
        const currentHeight = cardShell.offsetHeight;
        cardShell.style.height = currentHeight + 'px';
        
        // 1. Fade out current content
        formContent.classList.add('is-leaving');
        cardShell.classList.add('is-transitioning');
        cardShell.style.pointerEvents = 'none';
        
        setTimeout(() => {
            // Empty the form content
            formContent.innerHTML = '';
            formContent.classList.remove('is-leaving');
            
            // Shrink the card to just fit the logo (approx 140px height)
            cardShell.style.height = '140px';
            
            // 2 & 3. Show logo glyph and pulse
            transitionGlyph.classList.add('show');
            
            setTimeout(() => {
                transitionGlyph.classList.remove('show');
                
                setTimeout(() => {
                    // 4. Inject new content
                    injectTemplate(viewType);
                    cardShell.classList.remove('is-transitioning');
                    
                    // Measure natural height of new content
                    cardShell.style.height = 'auto';
                    const targetHeight = cardShell.offsetHeight;
                    
                    // Reset to 140px to prepare for expansion transition
                    cardShell.style.height = '140px';
                    
                    // Start form enter animation (opacity 0 -> 1)
                    formContent.classList.add('is-entering');
                    
                    // Force a reflow
                    void cardShell.offsetHeight;
                    
                    // Expand to target height
                    cardShell.style.height = targetHeight + 'px';
                    
                    // Trigger the light sweep on the primary button
                    const primaryBtn = formContent.querySelector('.primary-btn');
                    if (primaryBtn) {
                        primaryBtn.classList.add('sweep-animate');
                    }
                    
                    setTimeout(() => {
                        formContent.classList.remove('is-entering');
                        if (primaryBtn) {
                            primaryBtn.classList.remove('sweep-animate');
                        }
                        cardShell.style.height = 'auto'; // Reset for responsive layout
                        cardShell.style.pointerEvents = 'auto';
                        isAnimating = false;
                    }, 500); // Wait for enter animation
                    
                }, 300); // Wait for glyph to fade out
                
            }, 600); // Wait for pulse to finish
            
        }, 300); // Wait for fade out
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
