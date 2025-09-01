function loadComponent(id, file) {
  fetch(file)
    .then(res => res.text())
    .then(data => document.getElementById(id).innerHTML = data)
    .catch(err => console.error(`Error loading ${file}:`, err));
}

// Load header and footer automatically
document.addEventListener("DOMContentLoaded", () => {
  loadComponent("header", "header.html");
  loadComponent("footer", "footer.html");
});
