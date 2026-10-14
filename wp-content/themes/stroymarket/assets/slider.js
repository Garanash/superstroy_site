(function () {
  var root = document.querySelector(".sm-slider");
  if (!root) {
    return;
  }
  var track = root.querySelector(".sm-slider-track");
  var slides = Array.prototype.slice.call(root.querySelectorAll(".sm-slide"));
  var dots = Array.prototype.slice.call(root.querySelectorAll(".sm-slider-dot"));
  var prev = root.querySelector(".sm-slider-prev");
  var next = root.querySelector(".sm-slider-next");
  if (!track || slides.length < 2) {
    return;
  }

  var index = 0;
  var timer = 0;
  var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function show(nextIndex) {
    index = (nextIndex + slides.length) % slides.length;
    track.style.transform = "translateX(" + index * -100 + "%)";
    slides.forEach(function (slide, item) {
      var current = item === index;
      slide.classList.toggle("is-current", current);
      slide.setAttribute("aria-hidden", current ? "false" : "true");
    });
    dots.forEach(function (dot, item) {
      dot.setAttribute("aria-current", item === index ? "true" : "false");
    });
  }

  function stop() {
    window.clearInterval(timer);
  }

  function play() {
    stop();
    if (reduced) {
      return;
    }
    timer = window.setInterval(function () {
      show(index + 1);
    }, 6000);
  }

  if (prev) {
    prev.addEventListener("click", function () {
      show(index - 1);
      play();
    });
  }
  if (next) {
    next.addEventListener("click", function () {
      show(index + 1);
      play();
    });
  }
  dots.forEach(function (dot, item) {
    dot.addEventListener("click", function () {
      show(item);
      play();
    });
  });
  root.addEventListener("mouseenter", stop);
  root.addEventListener("mouseleave", play);
  root.addEventListener("focusin", stop);
  root.addEventListener("focusout", play);
  play();
})();
