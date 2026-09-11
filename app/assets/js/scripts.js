// <!-- JavaScript to handle screen resizing -->
function updateSettingsText() {
  var settingsText = document.getElementById('settings-text');
  if (window.matchMedia('(max-width: 720px)').matches) {
    settingsText.innerHTML = '<i class="fas fa-cog"></i>';  // Show cog icon for Settings
  } else {
    settingsText.textContent = 'Settings';  // Show "Settings" text for larger screens
  }
}

// Run on initial load
updateSettingsText();

// Add an event listener for screen resize
window.addEventListener('resize', updateSettingsText);




// this is for new model after clicked on read button

document.addEventListener('DOMContentLoaded', function() {
  const newsItems = document.querySelectorAll('.news-item');
  newsItems.forEach(item => {
      item.addEventListener('click', function() {
          const title = this.dataset.title;
          const description = this.dataset.description;
          const image = this.dataset.image;
          const date = this.dataset.date;
          const url = this.dataset.url;

          document.getElementById('newsModalLabel').innerText = title;
          document.getElementById('modal-description').innerText = description;
          document.getElementById('modal-image').src = image;
          document.getElementById('modal-date').innerText = date;
          document.getElementById('modal-url').href = url;

          var modal = new bootstrap.Modal(document.getElementById('newsModal'));
          modal.show();
      });
  });
});