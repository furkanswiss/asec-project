// javascript/hero-asec.js
document.addEventListener("DOMContentLoaded", function () {
  const wrapper = document.querySelector(".hero-asec-intro");
  if (!wrapper) return;

  const typing = wrapper.querySelector(".typing");
  const animated = wrapper.querySelector(".animated-asec");

  // 1) Parantezler içeri girer
  setTimeout(() => wrapper.classList.add("in"), 200);

  // 2) Parantezler açılır + typing başlar
  setTimeout(() => {
    wrapper.classList.add("apart");
    if (typing) typing.classList.add("go");
  }, 1200);

  // 3) Typing bittiğinde finali göster
  if (typing) {
    typing.addEventListener(
      "animationend",
      function (e) {
        if (e.animationName !== "typing") return;
        // typing'i yumuşakça gizle, sonra final göster
        typing.style.transition = "opacity 0.45s ease";
        typing.style.opacity = 0;
        setTimeout(() => {
          typing.style.display = "none";
          if (animated) animated.classList.add("show");
          wrapper.classList.add("merge");
        }, 480);
      },
      { once: true }
    );
  } else {
    // eğer typing yoksa doğrudan finali göster
    if (animated) setTimeout(() => animated.classList.add("show"), 1400);
  }
});
