const dropdownBtn = document.getElementById('dropdown-btn');
const dropdownMenu = document.getElementById('dropdown-menu');
const dropdownContainer = document.querySelector('.dropdown');

// Toggle the 'open' class on button click
dropdownBtn.addEventListener('click', (event) => {
  event.stopPropagation(); // Prevents the window click event from firing immediately
  dropdownContainer.classList.toggle('open');
});

// Close the dropdown if the user clicks anywhere outside of it
window.addEventListener('click', (event) => {
  if (!dropdownContainer.contains(event.target)) {
    dropdownContainer.classList.remove('open');
  }
});