const translateBtn = document.getElementById('translate-btn');
const textToTranslate = document.getElementById('text-to-translate');

const kannadaText = [
    "ʻವಿಪ್ರವಾರ್ತೆʼ  ನಿಮ್ಮ ಧ್ವನಿ, ನಿಮ್ಮ ವೇದಿಕೆ",
    "`ವಿಪ್ರವಾರ್ತೆ' ಎಂಬ ಹೆಸರು ಕೇಳಿದ ಕ್ಷಣದಲ್ಲಿ ಎರಡು ಪ್ರಶ್ನೆಗಳು ತಕ್ಷಣ ಉದ್ಭವಿಸಬಹುದು –",
    "೧. ಇದು ಬ್ರಾಹ್ಮಣ ಸಮಾಜಕ್ಕೆ ಸಂಬಂಧಿಸಿದ ಸುದ್ದಿಗಳಿಗೆ ಮಾತ್ರ ಸೀಮಿತವಾದ ಪೋರ್ಟಲ್‌ವೇ?",
    "೨. ಈಗಾಗಲೇ ಸಾವಿರಾರು ಸುದ್ದಿ ಏಜೆನ್ಸಿಗಳು, ನೂರಾರು ಟಿವಿ ಚಾನೆಲ್‌ಗಳು, ಪತ್ರಿಕೆಗಳು, ಸಾವಿರಾರು ವೆಬ್‌ಸೈಟ್‌ಗಳು ಇರುವಾಗ, ಇನ್ನೊಂದು ಪ್ರತ್ಯೇಕ ʻವಿಪ್ರ ನ್ಯೂಸ್ʼ ಪೋರ್ಟಲ್‌ ಬೇಕೆ?",
    "ಈ ಪ್ರಶ್ನೆಗಳು ನಿಜಕ್ಕೂ ಸೂಕ್ತವಾದವು. ಆದರೆ ಅದಕ್ಕೆ ಉತ್ತರವು ಸ್ಪಷ್ಟವಾಗಿದೆ.",
    "ಇಂದಿನ ಮಾಧ್ಯಮ ಜಗತ್ತಿನಲ್ಲಿ ಪೂರ್ಣಪ್ರಮಾಣದಲ್ಲಿ ಸಮಗ್ರವಾಗಿ ಬ್ರಾಹ್ಮಣ ಸಮುದಾಯವನ್ನು ಪ್ರತಿನಿಧಿಸುವ ಮಾಧ್ಯಮವಿಲ್ಲ. ಕೆಲವು ತ್ರಿಮತಸ್ಥ ಮಠಗಳಿಗೆ, ತಮ್ಮ ತಮ್ಮ ಮತಕ್ಕೆ ಅಥವಾ ಆಯಾ ಕ್ಷೇತ್ರಗಳಿಗೆ ಸೀಮಿತವಾಗಿವೆ. ಇದು ನಮ್ಮವರ ಬಹುತೇಕ ಅಭಿಪ್ರಾಯಗಳು, ಸಮಸ್ಯೆಗಳು, ಸಮಾಜದ ಒಳಗಿನ ಅಂತರಂಗದ ವಿಚಾರಗಳು ಬಹುಪಾಲು ಅನುರಣಿತವಾಗದೇ ಉಳಿದಿವೆ. ನಮ್ಮೊಳಗಿನ ಚಿಂತನೆಗಳು, ಚರ್ಚೆಗಳು, ನಮ್ಮವರ ಸಾಧನೆಗಳೆ ನಮಗೆ ಶಕ್ತಿ – ಆ ಶಕ್ತಿಯನ್ನು ಒಂದು ಮಾಡಬಲ್ಲ ಒಂದು ಸಮಗ್ರ ವೇದಿಕೆ ಅಗತ್ಯವಿದೆ, ಎಂಬ ಬಲವಾದ ನಂಬಿಕೆ ಬಹಳ ದಿನಗಳಲ್ಲಿ, ಸಾಕಷ್ಟು ವೇದಿಕೆಗಳಲ್ಲಿ ಕೇಳಿಬರುತ್ತಿತ್ತು.",
    "ಈ ಅವಶ್ಯಕತೆಯಿಂದಲೇ ಸಮಾನಮನಸ್ಕರು ಹಾಗೂ ಮಾಧ್ಯಮ ಕ್ಷೇತ್ರದಲ್ಲಿ ಸಕ್ರಿಯರಾಗಿರುವ ವಿಪ್ರರು ಸೇರಿಕೊಂಡ 'ವಿಕಾಸ' ಸಂಘಟನೆ ಒಂದು ದಿಟ್ಟ ಹೆಜ್ಜೆಯಿಟ್ಟಿದೆ. ವಿಕಾಸ ಸಂಘಟನೆಯ ಮೇಲ್ವಿಚಾರಣೆಯಲ್ಲಿ `ವಿಪ್ರ ವಾರ್ತೆ' ಎಂಬ ವಿನೂತನ ತಂತ್ರಜ್ಞಾನಗಳುಳ್ಳ ಡಿಜಿಟಲ್‌ ಮಾಧ್ಯಮವನ್ನು ಆರಂಭಿಸಿದ್ದೇವೆ.",
    "ಇದು ನಿಮ್ಮ ಧ್ವನಿ – ಸಮಸ್ತ ತ್ರಿಮತಸ್ಥ ಬ್ರಾಹ್ಮಣರ ಧ್ವನಿ.ಎಲ್ಲರೂ ಇಲ್ಲಿ ಆತ್ಮೀಯವಾಗಿ ಬೆರೆಯಬಲ್ಲೆವು.ಇಲ್ಲಿ ಸಂಪ್ರದಾಯವಿದೆ, ತಂತ್ರಜ್ಞಾನವೂ ಇದೆ, ಧರ್ಮವಿದೆ, ದಿಶೆಯೂ ಇದೆ.ಸಂಸ್ಕೃತಿಯ ಜೊತೆ ವಿಜ್ಞಾನವನ್ನೂ ಬೆಸೆದು ಬೆಳೆಸುವ ಸಮಕಾಲೀನ ಪ್ರಯತ್ನವಿದು.",
    "ಇದು ಸಾಮಾನ್ಯ ಪೋರ್ಟಲ್‌ಗಳಂತಲ್ಲ – ವಿಶಿಷ್ಟವೂ ವಿಭಿನ್ನವೂ ಆಗಿರುವ`ವಿಪ್ರವಾರ್ತೆ'ಯನ್ನು ಬೆಳೆಸುವುದು, ಉಳಿಸುವುದು, ಉಪಯೋಗಿಸುವ ಜವಾಬ್ದಾರಿ ನಮ್ಮೆಲ್ಲರಿಗೂ ಇದೆ."
];

const englishText = [
    "VipraVarta",
    "Your Voice, Your Platform",
    "The moment one hears the name VipraVarta, two natural questions arise:",
    "Is this portal meant exclusively for news related to the Brahmin community?",
    "With thousands of news agencies, hundreds of TV channels, newspapers, and countless websites already available, is there really a need for a separate Vipra news portal?",
    "These questions are perfectly valid. But the answer is clear.",
    "In today’s media landscape, there is no platform that comprehensively represents the Brahmin community in its entirety. A few may exist, but they are mostly limited to specific mathas, sects, or religious institutions. As a result, many of our internal perspectives, challenges, and cultural narratives remain unheard or underrepresented. Our thoughts, dialogues, and accomplishments—these are our true strengths. And there has long been a growing call for a unified platform to harness and amplify this strength.",
    "It is to address this very need that a group of like-minded and media-active Brahmins have come together to take a bold step through the organization News Junction. Under its leadership, VipraVarta, a new-age digital media platform equipped with modern technologies, has been launched.",
    "This is your voice – the collective voice of all Brahmins, across the tri-matha traditions. A space where everyone can come together in harmony.",
    "Here, tradition meets technology. Faith walks alongside forward-thinking direction. This is a contemporary endeavor that blends heritage with science.",
    "Unlike generic portals, VipraVarta is distinct and unique.",
    "Nurturing, preserving, and building this platform is a shared responsibility – one that belongs to all of us."
];

let isKannada = true;

function showKannada() {
    document.getElementById('text-to-translate').style.display = 'block';
    document.getElementById('english-text').style.display = 'none';
    translateBtn.innerText = 'English'; // Translate to English (in Kannada)
    isKannada = true;
}

function showEnglish() {
    document.getElementById('text-to-translate').style.display = 'none';
    document.getElementById('english-text').innerHTML = englishText.map(line => `<p>${line}</p>`).join('');
    document.getElementById('english-text').style.display = 'block';
    translateBtn.innerText = 'ಕನ್ನಡ.';
    isKannada = false;
}

translateBtn.addEventListener('click', function() {
    if (isKannada) {
        showEnglish();
    } else {
        showKannada();
    }
});

// Initialize with Kannada view and correct button text
showKannada();

// Hamburger menu logic
const hamburgerBtn = document.getElementById('hamburger-btn');
const mobileMenu = document.getElementById('mobile-menu');

hamburgerBtn.addEventListener('click', function() {
    const isActive = hamburgerBtn.classList.toggle('active');
    mobileMenu.classList.toggle('active', isActive);
    hamburgerBtn.setAttribute('aria-expanded', isActive);
});

// Close mobile menu when clicking outside or resizing
window.addEventListener('click', function(e) {
    if (mobileMenu.classList.contains('active') && !mobileMenu.contains(e.target) && !hamburgerBtn.contains(e.target)) {
        mobileMenu.classList.remove('active');
        hamburgerBtn.classList.remove('active');
        hamburgerBtn.setAttribute('aria-expanded', 'false');
    }
});
window.addEventListener('resize', function() {
    if (window.innerWidth > 700) {
        mobileMenu.classList.remove('active');
        hamburgerBtn.classList.remove('active');
        hamburgerBtn.setAttribute('aria-expanded', 'false');
    }
});
