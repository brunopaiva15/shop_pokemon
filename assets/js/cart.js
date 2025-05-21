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
    Swal.fire({
      toast: true,
      position: "top-end",
      icon: type === "success" ? "success" : "error",
      title: message,
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,
      customClass: {
        popup: "swal2-custom-toast",
      },
      didOpen: (toast) => {
        toast.addEventListener("mouseenter", () => Swal.close());
      },
    });
  }
});
