// assets/js/admin.js

document.addEventListener("DOMContentLoaded", function () {
  // Gestion des confirmations de suppression
  const deleteButtons = document.querySelectorAll(".delete-confirm");

  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (
        !confirm(
          "Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible."
        )
      ) {
        e.preventDefault();
      }
    });
  });

  // Prévisualisation des images
  const imageInput = document.getElementById("image_upload");
  const imagePreview = document.getElementById("image_preview");
  const currentImage = document.getElementById("current_image");

  if (imageInput && imagePreview) {
    imageInput.addEventListener("change", function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
          // Cacher l'image actuelle si elle existe
          if (currentImage) {
            currentImage.style.display = "none";
          }

          // Afficher la nouvelle image
          imagePreview.src = e.target.result;
          imagePreview.style.display = "block";
        };

        reader.readAsDataURL(this.files[0]);
      }
    });
  }

  // Filtrer les tableaux admin
  const tableFilter = document.getElementById("table_filter");
  const tableRows = document.querySelectorAll("table tbody tr");

  if (tableFilter) {
    tableFilter.addEventListener("input", function () {
      const query = this.value.toLowerCase();

      tableRows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
          row.style.display = "";
        } else {
          row.style.display = "none";
        }
      });
    });
  }

  // Gestion du statut des commandes
  const orderStatusSelects = document.querySelectorAll(".order-status-select");

  orderStatusSelects.forEach((select) => {
    select.addEventListener("change", function () {
      const orderId = this.dataset.orderId;
      const status = this.value;

      // Mettre à jour le statut via AJAX
      fetch("order-status-ajax.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `order_id=${orderId}&status=${status}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Changer la classe de la ligne
            const row = this.closest("tr");

            // Supprimer les classes de statut existantes
            row.classList.remove(
              "bg-yellow-100",
              "bg-green-100",
              "bg-red-100",
              "bg-blue-100"
            );

            // Ajouter la nouvelle classe
            switch (status) {
              case "pending":
                row.classList.add("bg-yellow-100");
                break;
              case "completed":
                row.classList.add("bg-green-100");
                break;
              case "cancelled":
                row.classList.add("bg-red-100");
                break;
              case "processing":
                row.classList.add("bg-blue-100");
                break;
            }

            // Notification
            showAdminNotification("Statut mis à jour avec succès", "success");
          } else {
            showAdminNotification("Erreur: " + data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showAdminNotification("Une erreur est survenue", "error");
        });
    });
  });

  // Fonction pour afficher des notifications admin
  function showAdminNotification(message, type) {
    // Vérifier si une notification existe déjà
    const existingNotification = document.querySelector(".admin-notification");
    if (existingNotification) {
      existingNotification.remove();
    }

    // Créer la notification
    const notification = document.createElement("div");
    notification.className = `admin-notification fixed bottom-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
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

  // Gestion de l'affichage des détails de commande
  const orderDetailButtons = document.querySelectorAll(".order-details-button");

  orderDetailButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const orderId = this.dataset.orderId;
      const detailsContainer = document.getElementById(
        `order-details-${orderId}`
      );

      if (detailsContainer) {
        detailsContainer.classList.toggle("hidden");
        this.innerHTML = detailsContainer.classList.contains("hidden")
          ? '<i class="fas fa-eye"></i> Voir détails'
          : '<i class="fas fa-eye-slash"></i> Masquer détails';
      }
    });
  });
});
