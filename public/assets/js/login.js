document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("auth-container");
  const showSignup = document.getElementById("show-signup");
  const showLogin = document.getElementById("show-login");

  showSignup.addEventListener("click", (e) => {
    e.preventDefault();
    container.classList.add("signup-mode");
  });

  showLogin.addEventListener("click", (e) => {
    e.preventDefault();
    container.classList.remove("signup-mode");
  });
});
