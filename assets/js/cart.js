// assets/js/cart.js

document.addEventListener("DOMContentLoaded", function () {
  // Gestion des boutons d'ajout au panier
  const addToCartButtons = document.querySelectorAll(".add-to-cart");

  addToCartButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const cardId = this.dataset.cardId;
      const quantity = this.closest(".card-item").querySelector(
        ".quantity-input"
      )
        ? parseInt(
            this.closest(".card-item").querySelector(".quantity-input").value
          )
        : 1;

      // Animation
      this.classList.add("add-to-cart-pulse");
      setTimeout(() => {
        this.classList.remove("add-to-cart-pulse");
      }, 500);

      // Ajout au panier via AJAX
      fetch("cart-ajax.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `action=add&card_id=${cardId}&quantity=${quantity}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Mettre à jour l'icône du panier
            const cartCountElement =
              document.querySelector(".fa-shopping-cart").nextElementSibling;
            if (cartCountElement) {
              cartCountElement.textContent = data.cart_count;
            } else {
              const cartIcon = document.querySelector(".fa-shopping-cart");
              const countSpan = document.createElement("span");
              countSpan.className =
                "absolute -top-2 -right-2 bg-yellow-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs";
              countSpan.textContent = data.cart_count;
              cartIcon.parentNode.appendChild(countSpan);
            }

            // Notification
            showNotification("Carte ajoutée au panier !", "success");
          } else {
            showNotification("Erreur: " + data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showNotification("Une erreur est survenue", "error");
        });
    });
  });

  // Gestion des boutons de quantité dans le panier
  const quantityButtons = document.querySelectorAll(".quantity-modifier");

  quantityButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const input = this.closest(".quantity-selector").querySelector("input");
      const currentValue = parseInt(input.value);
      const increment = this.dataset.modifier === "plus" ? 1 : -1;

      // Mettre à jour la valeur
      const newValue = Math.max(1, currentValue + increment);
      input.value = newValue;

      // Si on est dans le panier, mettre à jour via AJAX
      if (this.closest(".cart-item")) {
        const cardId = this.closest(".cart-item").dataset.cardId;
        updateCartItem(cardId, newValue);
      }
    });
  });

  // Mise à jour des articles du panier via AJAX
  function updateCartItem(cardId, quantity) {
    fetch("cart-ajax.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=update&card_id=${cardId}&quantity=${quantity}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Mettre à jour le sous-total de l'article
          const subtotalElement = document.querySelector(
            `.cart-item[data-card-id="${cardId}"] .subtotal`
          );
          if (subtotalElement) {
            subtotalElement.textContent = data.item_subtotal;
          }

          // Mettre à jour le total du panier
          const cartTotalElement = document.querySelector(".cart-total");
          if (cartTotalElement) {
            cartTotalElement.textContent = data.cart_total;
          }

          // Mettre à jour le nombre d'articles dans l'icône du panier
          const cartCountElement =
            document.querySelector(".fa-shopping-cart").nextElementSibling;
          if (cartCountElement) {
            cartCountElement.textContent = data.cart_count;
          }
        } else {
          showNotification("Erreur: " + data.message, "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showNotification("Une erreur est survenue", "error");
      });
  }

  // Gestion des boutons de suppression d'articles du panier
  const removeButtons = document.querySelectorAll(".remove-from-cart");

  removeButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const cardId = this.closest(".cart-item").dataset.cardId;

      // Supprimer via AJAX
      fetch("cart-ajax.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `action=remove&card_id=${cardId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Supprimer l'élément du DOM
            this.closest(".cart-item").remove();

            // Mettre à jour le total du panier
            const cartTotalElement = document.querySelector(".cart-total");
            if (cartTotalElement) {
              cartTotalElement.textContent = data.cart_total;
            }

            // Mettre à jour le nombre d'articles dans l'icône du panier
            const cartCountElement =
              document.querySelector(".fa-shopping-cart").nextElementSibling;
            if (cartCountElement) {
              if (data.cart_count > 0) {
                cartCountElement.textContent = data.cart_count;
              } else {
                cartCountElement.remove();

                // Afficher un message si le panier est vide
                const cartContainer = document.querySelector(".cart-container");
                const emptyCartMessage = document.querySelector(
                  ".empty-cart-message"
                );

                if (cartContainer && !emptyCartMessage) {
                  cartContainer.innerHTML =
                    '<div class="empty-cart-message p-8 text-center">' +
                    '<i class="fas fa-shopping-cart text-4xl text-gray-400 mb-4"></i>' +
                    '<h2 class="text-2xl font-bold mb-2">Votre panier est vide</h2>' +
                    '<p class="text-gray-600 mb-4">Ajoutez des cartes à votre collection !</p>' +
                    '<a href="index.php" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">' +
                    "Parcourir les cartes</a>" +
                    "</div>";
                }
              }
            }

            showNotification("Article supprimé du panier", "success");
          } else {
            showNotification("Erreur: " + data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showNotification("Une erreur est survenue", "error");
        });
    });
  });

  // Fonction pour afficher des notifications
  function showNotification(message, type) {
    // Vérifier si une notification existe déjà
    const existingNotification = document.querySelector(".notification");
    if (existingNotification) {
      existingNotification.remove();
    }

    // Créer la notification
    const notification = document.createElement("div");
    notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
      type === "success" ? "bg-green-500" : "bg-red-500"
    } text-white`;
    notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${
                  type === "success"
                    ? "fa-check-circle"
                    : "fa-exclamation-circle"
                } mr-2"></i>
                <span>${message}</span>
            </div>
        `;

    document.body.appendChild(notification);

    // Faire disparaître la notification après 3 secondes
    setTimeout(() => {
      notification.classList.add("opacity-0");
      setTimeout(() => {
        notification.remove();
      }, 300);
    }, 3000);
  }
});
