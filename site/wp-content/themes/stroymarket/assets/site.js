(function () {
  function dialog(id) {
    var node = document.getElementById(id);
    if (!node || typeof node.showModal !== "function") {
      return;
    }
    document.querySelectorAll("dialog").forEach(function (open) {
      if (open !== node && open.open) {
        open.close();
      }
    });
    if (!node.open) {
      node.showModal();
    }
  }

  document.addEventListener("click", function (event) {
    var opener = event.target.closest("[data-sm-dialog]");
    if (opener) {
      event.preventDefault();
      dialog(opener.getAttribute("data-sm-dialog"));
    }
  });

  var phonePattern = /^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/;
  function phoneDigits(value) {
    var digits = String(value || "").replace(/\D/g, "");
    if (digits.charAt(0) === "8") {
      digits = "7" + digits.slice(1);
    }
    if (digits.charAt(0) === "7") {
      digits = digits.slice(1);
    }
    return digits.slice(0, 10);
  }
  function formatPhone(digits) {
    if (!digits) {
      return "";
    }
    var text = "+7 (" + digits.slice(0, 3);
    if (digits.length < 3) {
      return text;
    }
    text += ")";
    if (digits.length === 3) {
      return text;
    }
    text += " " + digits.slice(3, 6);
    if (digits.length <= 6) {
      return text;
    }
    text += "-" + digits.slice(6, 8);
    if (digits.length <= 8) {
      return text;
    }
    return text + "-" + digits.slice(8, 10);
  }
  function paintPhone(input, digits) {
    var next = digits ? formatPhone(digits) : "";
    if (input.value !== next) {
      input.value = next;
    }
    input.setCustomValidity(next && !phonePattern.test(next) ? "Введите телефон в формате +7 (999) 999-99-99" : "");
  }
  function phoneField(target) {
    return target && target.closest ? target.closest("[data-phone-mask]") : null;
  }
  document.addEventListener("focusin", function (event) {
    var input = phoneField(event.target);
    if (input && input.value === "") {
      input.value = "+7 (";
    }
  });
  document.addEventListener("focusout", function (event) {
    var input = phoneField(event.target);
    if (!input) {
      return;
    }
    if (input.value === "+7 (" || input.value === "+7") {
      input.value = "";
    }
    paintPhone(input, phoneDigits(input.value));
  });
  document.addEventListener("input", function (event) {
    var input = phoneField(event.target);
    if (!input) {
      return;
    }
    paintPhone(input, phoneDigits(input.value));
  });
  document.addEventListener("keydown", function (event) {
    var input = phoneField(event.target);
    if (!input || event.key !== "Backspace") {
      return;
    }
    var digits = phoneDigits(input.value);
    if (!digits) {
      return;
    }
    event.preventDefault();
    paintPhone(input, digits.slice(0, -1));
    if (!phoneDigits(input.value)) {
      input.value = "+7 (";
    }
  });
  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!form || !form.classList || !form.classList.contains("sm-lead-form")) {
      return;
    }
    var name = form.querySelector("[name='sm_name']");
    var phone = form.querySelector("[data-phone-mask]");
    if (name) {
      name.setCustomValidity(name.value.trim() === "" ? "Укажите имя" : "");
    }
    if (phone) {
      phone.setCustomValidity(phonePattern.test(phone.value) ? "" : "Введите телефон в формате +7 (999) 999-99-99");
    }
    if (!form.checkValidity()) {
      event.preventDefault();
      form.reportValidity();
    }
  });

  document.querySelectorAll("[data-calc]").forEach(function (form) {
    var result = form.querySelector(".sm-calc-result");
    function money(value) {
      return Math.round(value).toLocaleString("ru-RU") + " ₽";
    }
    function render() {
      var data = new FormData(form);
      var kind = form.getAttribute("data-calc");
      if (kind === "delivery") {
        var zone = data.get("zone");
        var kmField = form.querySelector("[data-km]");
        if (kmField) {
          kmField.hidden = zone !== "region";
        }
        if (zone === "other") {
          result.textContent = "Для другого города стоимость посчитаем по маршруту и напишем в счёте.";
          return;
        }
        var base = Number(form.getAttribute("data-moscow") || 0);
        var rate = Number(form.getAttribute("data-km-rate") || 0);
        var sum = base + (zone === "region" ? Number(data.get("km") || 0) * rate : 0);
        result.textContent = "Ориентир: " + money(sum) + ".";
        return;
      }
      if (kind === "cement") {
        var volume = Number(data.get("length")) * Number(data.get("width")) * Number(data.get("height"));
        var kg = volume * Number(data.get("grade"));
        var bags = Math.ceil(kg / 50);
        result.textContent = "Объём " + volume.toFixed(2) + " м³, цемента " + Math.ceil(kg) + " кг, это " + bags + " мешков по 50 кг.";
        return;
      }
      if (kind === "frame") {
        var length = Number(data.get("length"));
        var height = Number(data.get("height"));
        var step = Number(data.get("step")) / 1000;
        var board = Number(data.get("board"));
        var studs = Math.ceil(length / step) + 1;
        var meters = studs * height + length * 3;
        var count = Math.ceil(meters / board);
        result.textContent = "Стоек " + studs + ", погонных метров " + meters.toFixed(1) + ", досок по " + board + " м — " + count + " шт.";
      }
    }
    form.addEventListener("input", render);
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      render();
    });
    render();
  });

  var power = document.querySelector("[data-calc='power']");
  if (power) {
    var lines = power.querySelector(".sm-lines");
    var output = power.querySelector(".sm-calc-result");
    function section(amps) {
      if (amps <= 10) return "1,5 мм², автомат 10 А";
      if (amps <= 16) return "2,5 мм², автомат 16 А";
      if (amps <= 25) return "4 мм², автомат 25 А";
      if (amps <= 32) return "6 мм², автомат 32 А";
      return "отдельный расчёт, ток выше 32 А";
    }
    function renderPower() {
      var names = power.querySelectorAll("[name='line_name']");
      var powers = power.querySelectorAll("[name='line_power']");
      var html = document.createDocumentFragment();
      names.forEach(function (name, index) {
        var watts = Number(powers[index].value);
        if (!watts) return;
        var amps = watts / 220;
        var row = document.createElement("p");
        var title = document.createElement("strong");
        title.textContent = name.value;
        row.appendChild(title);
        row.appendChild(document.createTextNode(": " + amps.toFixed(1) + " А, " + section(amps)));
        html.appendChild(row);
      });
      output.replaceChildren(html);
    }
    power.addEventListener("input", renderPower);
    var add = power.querySelector("[data-add-line]");
    if (add) {
      add.addEventListener("click", function () {
        var row = document.createElement("p");
        row.innerHTML = '<label>Линия<input type="text" name="line_name" value="Новая линия"></label> <label>Мощность, Вт<input type="number" name="line_power" min="1" value="1000"></label>';
        lines.appendChild(row);
        renderPower();
      });
    }
    renderPower();
  }

  var header = document.querySelector(".site-header");
  var side = document.querySelector(".sm-side");
  var footer = document.querySelector(".site-footer");
  var stuckScrollOffset = 48;
  var sideLastScroll = window.scrollY;
  var sideStickyTop = null;
  function syncHeader() {
    if (header) {
      header.classList.toggle("sm-stuck", window.scrollY > stuckScrollOffset);
      document.documentElement.style.setProperty("--sm-header-h", Math.ceil(header.getBoundingClientRect().height) + "px");
    }
    if (!side) {
      return;
    }
    side.style.height = "";
    side.style.maxHeight = "";
    side.style.visibility = "";
    if (getComputedStyle(side).position !== "sticky") {
      side.style.top = "";
      sideLastScroll = window.scrollY;
      return;
    }
    var headerBottom = header ? Math.ceil(header.getBoundingClientRect().bottom) : 210;
    var gap = 12;
    var height = side.offsetHeight;
    var minTop = Math.min(headerBottom, window.innerHeight - gap - height);
    var scrollY = window.scrollY;
    if (sideStickyTop === null) {
      sideStickyTop = headerBottom;
    }
    sideStickyTop = Math.min(headerBottom, Math.max(minTop, sideStickyTop - (scrollY - sideLastScroll)));
    if (footer) {
      var footerTop = footer.getBoundingClientRect().top;
      sideStickyTop = Math.min(sideStickyTop, footerTop - gap - height);
    }
    side.style.top = Math.round(sideStickyTop) + "px";
    sideLastScroll = scrollY;
  }
  syncHeader();
  window.addEventListener("resize", syncHeader);
  window.addEventListener("scroll", syncHeader, { passive: true });

  var cityNames = {
    Moscow: "Москва",
    "Saint Petersburg": "Санкт-Петербург",
    "St Petersburg": "Санкт-Петербург",
    Novosibirsk: "Новосибирск",
    Yekaterinburg: "Екатеринбург",
    Kazan: "Казань",
    "Nizhny Novgorod": "Нижний Новгород",
    Chelyabinsk: "Челябинск",
    Samara: "Самара",
    "Rostov-on-Don": "Ростов-на-Дону",
    Ufa: "Уфа",
    Krasnoyarsk: "Красноярск",
    Voronezh: "Воронеж",
    Perm: "Пермь",
    Volgograd: "Волгоград",
    Krasnodar: "Краснодар"
  };
  var confirmBox = document.querySelector("[data-sm-city-confirm]");
  function paintCity(city) {
    document.querySelectorAll("[data-sm-city-label]").forEach(function (node) {
      node.textContent = city;
    });
    var office = document.querySelector("[data-sm-office]");
    if (office) {
      var base = office.getAttribute("data-sm-office") || "";
      office.textContent = city === "Москва" ? base : "Офис: " + base;
    }
    document.querySelectorAll("[data-sm-city-pick]").forEach(function (button) {
      button.classList.toggle("is-current", button.getAttribute("data-sm-city-pick") === city);
    });
  }
  function rememberCity(city, confirmed) {
    try {
      localStorage.setItem("sm-city", city);
      if (confirmed) {
        localStorage.setItem("sm-city-ok", "1");
      }
    } catch (error) {
      return;
    }
    paintCity(city);
  }
  var savedCity = "";
  try {
    savedCity = localStorage.getItem("sm-city") || "";
  } catch (error) {
    savedCity = "";
  }
  if (savedCity) {
    paintCity(savedCity);
  } else {
    var request = typeof AbortSignal !== "undefined" && AbortSignal.timeout ? { signal: AbortSignal.timeout(2500) } : {};
    fetch("https://ipwho.is/", request)
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data && data.success && data.city) {
          rememberCity(cityNames[data.city] || data.city, false);
        }
        if (confirmBox && localStorage.getItem("sm-city-ok") !== "1") {
          confirmBox.hidden = false;
        }
      })
      .catch(function () {
        if (confirmBox) {
          confirmBox.hidden = false;
        }
      });
  }
  document.addEventListener("click", function (event) {
    if (event.target.closest("[data-sm-city-yes]")) {
      var current = document.querySelector(".sm-city-btn [data-sm-city-label]");
      rememberCity(current ? current.textContent : "Москва", true);
      if (confirmBox) {
        confirmBox.hidden = true;
      }
      return;
    }
    var picked = event.target.closest("[data-sm-city-pick]");
    if (picked) {
      rememberCity(picked.getAttribute("data-sm-city-pick") || "Москва", true);
      var cityDialog = document.getElementById("sm-dialog-city");
      if (cityDialog && cityDialog.open) {
        cityDialog.close();
      }
      if (confirmBox) {
        confirmBox.hidden = true;
      }
      return;
    }
    if (event.target.closest("[data-sm-city-open]")) {
      event.preventDefault();
      if (confirmBox) {
        confirmBox.hidden = true;
      }
      var picker = document.getElementById("sm-dialog-city");
      if (picker && typeof picker.showModal === "function") {
        picker.showModal();
      }
    }
  });

  var themeButton = document.querySelector(".sm-theme");
  if (themeButton) {
    themeButton.setAttribute("aria-pressed", document.documentElement.classList.contains("sm-dark") ? "true" : "false");
    themeButton.addEventListener("click", function () {
      var enabled = document.documentElement.classList.toggle("sm-dark");
      themeButton.setAttribute("aria-pressed", enabled ? "true" : "false");
      try {
        localStorage.setItem("sm-theme", enabled ? "dark" : "light");
      } catch (error) {
        return;
      }
    });
  }

  document.querySelectorAll(".sm-side-fly").forEach(function (fly) {
    document.body.appendChild(fly);
  });
  document.querySelectorAll(".sm-side-item[data-fly]").forEach(function (item) {
    var fly = document.getElementById(item.getAttribute("data-fly"));
    if (!fly) {
      return;
    }
    function openFly() {
      document.querySelectorAll(".sm-side-fly").forEach(function (other) {
        if (other !== fly) {
          other.hidden = true;
        }
      });
      fly.hidden = false;
      var rect = item.getBoundingClientRect();
      var top = rect.top;
      var limit = window.innerHeight - fly.offsetHeight - 8;
      fly.style.top = Math.max(8, Math.min(top, limit)) + "px";
      fly.style.left = rect.right + "px";
    }
    function leave(event) {
      var next = event.relatedTarget;
      if (next && (fly.contains(next) || item.contains(next))) {
        return;
      }
      fly.hidden = true;
    }
    item.addEventListener("mouseenter", openFly);
    item.addEventListener("mouseleave", leave);
    fly.addEventListener("mouseleave", leave);
  });
  var sideScroll = document.querySelector(".sm-side-scroll");
  function closeFlies() {
    document.querySelectorAll(".sm-side-fly").forEach(function (fly) {
      fly.hidden = true;
    });
  }
  if (sideScroll) {
    sideScroll.addEventListener("scroll", closeFlies);
  }
  window.addEventListener("scroll", closeFlies, { passive: true });
})();
