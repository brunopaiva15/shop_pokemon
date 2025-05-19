// assets/js/cart.js

document.addEventListener("DOMContentLoaded", function () {
  // Gestion des boutons "Ajouter au panier"
  const addToCartButtons = document.querySelectorAll(".add-to-cart");

  addToCartButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const cardId = this.dataset.cardId;
      const condition = this.dataset.condition;

      // Récupérer la quantité si disponible
      let quantity = 1;
      const quantityInput = document.querySelector(
        `.quantity-input[data-condition="${condition}"]`
      );
      if (quantityInput) {
        quantity = parseInt(quantityInput.value, 10) || 1;
      }

      // Appel AJAX pour ajouter au panier
      addToCart(cardId, condition, quantity);
    });
  });

  // Fonction pour ajouter au panier via AJAX
  function addToCart(cardId, condition, quantity) {
    const formData = new FormData();
    formData.append("card_id", cardId);
    formData.append("condition", condition);
    formData.append("quantity", quantity);

    fetch("add-to-cart.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Mettre à jour le compteur du panier
          updateCartCounter(data.cart_count);

          // Afficher une notification de succès
          showNotification(data.message, "success");
        } else {
          // Afficher une notification d'erreur
          showNotification(data.message, "error");
        }
      })
      .catch((error) => {
        console.error("Erreur:", error);
        showNotification("Une erreur est survenue", "error");
      });
  }

  // Fonction pour mettre à jour le compteur du panier
  function updateCartCounter(count) {
    const cartCounters = document.querySelectorAll(".cart-counter");
    cartCounters.forEach((counter) => {
      if (count > 0) {
        counter.textContent = count;
        counter.classList.remove("hidden");
      } else {
        counter.classList.add("hidden");
      }
    });
  }

  // Fonction pour afficher des notifications
  function showNotification(message, type) {
    const existing = document.querySelector(".notification");
    if (existing) existing.remove();

    const notification = document.createElement("div");
    notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
      type === "success" ? "bg-green-500" : "bg-red-500"
    } text-white transition-opacity duration-300`;
    notification.innerHTML = `
          <div class="flex items-center">
              <i class="fas ${
                type === "success" ? "fa-check-circle" : "fa-exclamation-circle"
              } mr-2"></i>
              <span>${message}</span>
          </div>
      `;

    document.body.appendChild(notification);

    // Faire disparaître après un délai
    setTimeout(() => {
      notification.classList.add("opacity-0");
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }
});
