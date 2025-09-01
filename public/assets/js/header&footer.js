function loadComponent(id, file) {
  fetch(file)   // go up one folder from /assets/js/
    .then(res => res.text())
    .then(data => document.getElementById(id).innerHTML = data)
    .catch(err => console.error(err));
}

document.addEventListener("DOMContentLoaded", () => {
  loadComponent("header", "includes/header.html");
  loadComponent("footer", "includes/footer.html");
});
