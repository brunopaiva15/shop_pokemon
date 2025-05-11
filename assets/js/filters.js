// assets/js/filters.js

document.addEventListener("DOMContentLoaded", function () {
  // Récupérer les éléments du DOM
  const seriesFilter = document.getElementById("series-filter");
  const conditionFilter = document.getElementById("condition-filter");
  const sortFilter = document.getElementById("sort-filter");
  const priceMinFilter = document.getElementById("price-min");
  const priceMaxFilter = document.getElementById("price-max");
  const applyFiltersBtn = document.getElementById("apply-filters");
  const resetFiltersBtn = document.getElementById("reset-filters");
  const mobileFilterToggle = document.getElementById("mobile-filter-toggle");
  const filterSidebar = document.getElementById("filter-sidebar");

  // Afficher/masquer les filtres sur mobile
  if (mobileFilterToggle && filterSidebar) {
    mobileFilterToggle.addEventListener("click", function () {
      filterSidebar.classList.toggle("hidden");
      mobileFilterToggle.innerHTML = filterSidebar.classList.contains("hidden")
        ? '<i class="fas fa-filter mr-1"></i> Afficher les filtres'
        : '<i class="fas fa-times mr-1"></i> Masquer les filtres';
    });
  }

  // Appliquer les filtres
  if (applyFiltersBtn) {
    applyFiltersBtn.addEventListener("click", function () {
      applyFilters();
    });
  }

  // Réinitialiser les filtres
  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener("click", function () {
      resetFilters();
    });
  }

  // Appliquer les filtres lorsque le tri change
  if (sortFilter) {
    sortFilter.addEventListener("change", function () {
      applyFilters();
    });
  }

  // Fonction pour appliquer les filtres
  function applyFilters() {
    const params = new URLSearchParams(window.location.search);

    // Mettre à jour les paramètres d'URL avec les valeurs des filtres
    updateUrlParam(params, "series", seriesFilter ? seriesFilter.value : null);
    updateUrlParam(
      params,
      "condition",
      conditionFilter ? conditionFilter.value : null
    );
    updateUrlParam(params, "sort", sortFilter ? sortFilter.value : null);
    updateUrlParam(
      params,
      "price_min",
      priceMinFilter && priceMinFilter.value ? priceMinFilter.value : null
    );
    updateUrlParam(
      params,
      "price_max",
      priceMaxFilter && priceMaxFilter.value ? priceMaxFilter.value : null
    );

    // Conserver le paramètre de recherche s'il existe
    const searchQuery = params.get("q");
    if (searchQuery) {
      params.set("q", searchQuery);
    }

    // Rediriger vers la nouvelle URL
    window.location.href = window.location.pathname + "?" + params.toString();
  }

  // Fonction pour réinitialiser les filtres
  function resetFilters() {
    if (seriesFilter) seriesFilter.value = "";
    if (conditionFilter) conditionFilter.value = "";
    if (sortFilter) sortFilter.value = "newest";
    if (priceMinFilter) priceMinFilter.value = "";
    if (priceMaxFilter) priceMaxFilter.value = "";

    // Conserver uniquement le paramètre de recherche s'il existe
    const params = new URLSearchParams(window.location.search);
    const searchQuery = params.get("q");

    if (searchQuery) {
      window.location.href =
        window.location.pathname + "?q=" + encodeURIComponent(searchQuery);
    } else {
      window.location.href = window.location.pathname;
    }
  }

  // Fonction pour mettre à jour un paramètre d'URL
  function updateUrlParam(params, key, value) {
    if (value) {
      params.set(key, value);
    } else {
      params.delete(key);
    }
  }

  // Initialiser les boutons d'expansion de série
  const seriesExpanders = document.querySelectorAll(".series-expander");

  seriesExpanders.forEach((button) => {
    button.addEventListener("click", function () {
      const seriesId = this.dataset.seriesId;
      const content = document.querySelector(`#series-content-${seriesId}`);

      content.classList.toggle("hidden");
      this.innerHTML = content.classList.contains("hidden")
        ? '<i class="fas fa-chevron-down"></i>'
        : '<i class="fas fa-chevron-up"></i>';
    });
  });
});
