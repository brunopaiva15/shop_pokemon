// assets/js/cart.js

document.addEventListener("DOMContentLoaded", function () {
  // Gestion des boutons d'ajout au panier
  const addToCartButtons = document.querySelectorAll(".add-to-cart");

  addToCartButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const cardId = this.dataset.cardId;
      let quantity = 1;

      // Chercher un input de quantité dans .card-item
      const cardItem = this.closest(".card-item");
      const qtyInCard = cardItem?.querySelector(".quantity-input");
      if (qtyInCard) {
        quantity = parseInt(qtyInCard.value, 10) || 1;
      } else {
        // Sinon, premier input du document (page détails)
        const quantityInput = document.querySelector(".quantity-input");
        if (quantityInput) {
          quantity = parseInt(quantityInput.value, 10) || 1;
        }
      }

      // Vérifier le stock maximum
      const maxStock = getMaxStock(this);
      if (quantity > maxStock) {
        showNotification(`Quantité limitée à ${maxStock} en stock`, "error");
        quantity = maxStock;
        return;
      }

      // Animation
      this.classList.add("add-to-cart-pulse");
      setTimeout(() => {
        this.classList.remove("add-to-cart-pulse");
      }, 500);

      // Construction de l'URL AJAX
      const baseUrl = window.location.pathname.includes("/")
        ? window.location.pathname.substring(
            0,
            window.location.pathname.lastIndexOf("/")
          )
        : "";

      fetch(baseUrl + "/cart-ajax.php", {
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
              document.querySelector(".fa-shopping-cart")?.nextElementSibling;
            if (cartCountElement) {
              cartCountElement.textContent = data.cart_count;
            } else {
              const cartIcon = document.querySelector(".fa-shopping-cart");
              if (cartIcon?.parentNode) {
                const countSpan = document.createElement("span");
                countSpan.className =
                  "absolute -top-2 -right-2 bg-yellow-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs";
                countSpan.textContent = data.cart_count;
                cartIcon.parentNode.appendChild(countSpan);
              }
            }

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

  // Fonction pour récupérer le stock maximum
  function getMaxStock(button) {
    const cardItem = button.closest(".card-item");
    const qtyInCard = cardItem?.querySelector(".quantity-input");
    const quantityInput =
      qtyInCard ?? document.querySelector(".quantity-input");

    if (!quantityInput) {
      return 999;
    }
    return parseInt(quantityInput.getAttribute("max") || "999", 10);
  }

  // Gestion des boutons de quantité (+ / -)
  document.querySelectorAll(".quantity-modifier").forEach((button) => {
    button.addEventListener("click", function () {
      const selector = this.closest(".quantity-selector");
      if (!selector) return;

      const input = selector.querySelector("input");
      if (!input) return;

      const currentValue = parseInt(input.value, 10) || 1;
      const increment = this.dataset.modifier === "plus" ? 1 : -1;
      const maxValue = parseInt(input.getAttribute("max") || "999", 10);

      input.value = Math.min(maxValue, Math.max(1, currentValue + increment));

      // Si on est dans le panier, mise à jour AJAX
      const cartItem = this.closest(".cart-item");
      if (cartItem) {
        updateCartItem(cartItem.dataset.cardId, parseInt(input.value, 10));
      }
    });
  });

  // Validation des inputs de quantité
  document.querySelectorAll(".quantity-input").forEach((input) => {
    input.addEventListener("change", function () {
      const maxValue = parseInt(this.getAttribute("max") || "999", 10);
      let currentValue = parseInt(this.value, 10) || 1;

      if (currentValue > maxValue) {
        this.value = maxValue;
        showNotification(`Quantité limitée à ${maxValue} en stock`, "error");
      } else if (currentValue < 1) {
        this.value = 1;
      }

      const cartItem = this.closest(".cart-item");
      if (cartItem) {
        updateCartItem(cartItem.dataset.cardId, parseInt(this.value, 10));
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
          const subtotalElement = document.querySelector(
            `.cart-item[data-card-id="${cardId}"] .subtotal`
          );
          if (subtotalElement) {
            subtotalElement.textContent = data.item_subtotal;
          }

          const cartTotalElement = document.querySelector(".cart-total");
          if (cartTotalElement) {
            cartTotalElement.textContent = data.cart_total;
          }

          const cartCountElement =
            document.querySelector(".fa-shopping-cart")?.nextElementSibling;
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

  // Gestion de la suppression d'articles
  document.querySelectorAll(".remove-from-cart").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const cartItem = this.closest(".cart-item");
      const cardId = cartItem?.dataset.cardId;
      if (!cardId) return;

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
            cartItem.remove();

            const cartTotalElement = document.querySelector(".cart-total");
            if (cartTotalElement) {
              cartTotalElement.textContent = data.cart_total;
            }

            const cartCountElement =
              document.querySelector(".fa-shopping-cart")?.nextElementSibling;
            if (cartCountElement) {
              if (data.cart_count > 0) {
                cartCountElement.textContent = data.cart_count;
              } else {
                cartCountElement.remove();
                const cartContainer = document.querySelector(".cart-container");
                if (cartContainer) {
                  cartContainer.innerHTML = `
                    <div class="empty-cart-message p-8 text-center">
                      <i class="fas fa-shopping-cart text-4xl text-gray-400 mb-4"></i>
                      <h2 class="text-2xl font-bold mb-2">Votre panier est vide</h2>
                      <p class="text-gray-600 mb-4">Ajoutez des cartes à votre collection !</p>
                      <a href="index.php" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                        Parcourir les cartes
                      </a>
                    </div>`;
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
    const existing = document.querySelector(".notification");
    if (existing) existing.remove();

    const notification = document.createElement("div");
    notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
      type === "success" ? "bg-green-500" : "bg-red-500"
    } text-white`;
    notification.innerHTML = `
      <div class="flex items-center">
        <i class="fas ${
          type === "success" ? "fa-check-circle" : "fa-exclamation-circle"
        } mr-2"></i>
        <span>${message}</span>
      </div>`;

    document.body.appendChild(notification);

    setTimeout(() => {
      notification.classList.add("opacity-0");
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }
});
