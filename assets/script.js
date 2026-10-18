document.addEventListener('DOMContentLoaded', function() {
    const voiceBtn = document.getElementById('voiceBtn');
    const searchInput = document.getElementById('searchInput');
    
    if (voiceBtn && searchInput) {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US';
            
            recognition.onstart = function() {
                voiceBtn.classList.add('listening');
                voiceBtn.textContent = '🎤 Listening...';
            };
            
            recognition.onend = function() {
                voiceBtn.classList.remove('listening');
                voiceBtn.textContent = '🎤 Voice';
            };
            
            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                searchInput.value = transcript;
                searchInput.form.submit();
            };
            
            voiceBtn.onclick = function() {
                recognition.start();
            };
        } else {
            voiceBtn.disabled = true;
            voiceBtn.textContent = '🎤 Not Supported';
            voiceBtn.style.background = '#ccc';
        }
    }
    
    const printBtns = document.querySelectorAll('.btn-print');
    printBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            window.print();
        });
    });
    
    const confirmBtns = document.querySelectorAll('.confirm-delete');
    confirmBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    const autoHideAlerts = document.querySelectorAll('.alert');
    autoHideAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

function showLocation(location) {
    window.location.href = 'map.php?location=' + encodeURIComponent(location);
}

function checkoutBook(bookCode) {
    window.location.href = 'checkout.php?book_code=' + encodeURIComponent(bookCode);
}

function searchBooks(query) {
    if (query) {
        window.location.href = 'search.php?q=' + encodeURIComponent(query);
    }
}