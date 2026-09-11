function reportPost(id) {
  const modal = $("#reportModal");
  modal.addClass("active"); // Use jQuery's addClass method
  $("#reportStreamId").val(id);
}

// Function to handle the report submission with AJAX
function report_stream(userId, streamId, reason) {
  // Prepare the data for the server using FormData
  const formData = new FormData();
  formData.append("userId", userId);
  formData.append("streamId", streamId);
  formData.append("reason", reason);

  // Perform the AJAX request with jQuery
  $.ajax({
    url: "/inc/php/report_stream.php",
    type: "POST",
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    success: function (data) {
      console.log(data);
      if (data.status === "success") {
        alert(data.message);
        window.location.reload(); // Reload the page after successful submission
      } else {
        alert("Error: " + data.message);
      }
    },
    error: function (xhr, status, error) {
      console.error("Error:", error);
      alert(
        "An error occurred while trying to report the post. Please try again later."
      );
    },
  });
}

// jQuery code to handle the 'Submit' button click
function submitReport() {
  // Get the selected reason from the dropdown
  const reason = $("#reportReasonSelect").val();
  const streamId = $("#reportStreamId").val();
  const otherReason = $("#otherReasonTextarea").val().trim(); // Get the value from the textarea

  // Ensure a reason is selected
  if (!reason) {
    alert("Please select a reason.");
    return;
  }

  // If "Others" is selected, ensure the textarea is filled
  let finalReason = reason;
  if (reason === "Others") {
    if (!otherReason) {
      alert("Please provide a reason.");
      return;
    }
    finalReason = otherReason; // Use the value from the textarea as the reason
  }

  // Call the report_stream function with the selected data
  report_stream(userId, streamId, finalReason);
}

function blockAccount(userId) {
  // Ask the user for confirmation before proceeding
  if (confirm("Are you sure you want to block this account?")) {
    $.ajax({
      url: "inc/php/blockAccount.php", // The PHP script to handle the blocking
      method: "POST",
      data: {
        act: "block",
        userId: userId,
      },
      success: function (response) {
        // Check if the block operation was successful
        if (response.status === "success") {
          alert("Blocked the account");
          window.location.reload(); // Reload the page to reflect the changes
        } else {
          alert(response.message); // Show error message if blocking fails
        }
      },
      error: function () {
        alert("There was an error while blocking the account.");
      },
    });
  }
}
// Dark mode functionality
document.addEventListener("DOMContentLoaded", function () {
  // Simple like functionality
  const actionButtons = document.querySelectorAll(".action-button");

  actionButtons.forEach((button) => {
    button.addEventListener("click", function () {
      if (this.querySelector(".fa-heart")) {
        this.classList.toggle("liked");
        const icon = this.querySelector(".fa-heart");
        if (this.classList.contains("liked")) {
          icon.classList.remove("far");
          icon.classList.add("fas");
        } else {
          icon.classList.remove("fas");
          icon.classList.add("far");
        }
      }
    });
  });

  // Create post functionality
  // const postButton = document.querySelector('.post-button');
  // const postTextarea = document.querySelector('.post-input textarea');

  // postButton.addEventListener('click', function () {
  //     const postText = postTextarea.value.trim();
  //     if (postText !== '') {
  //         createNewPost(postText);
  //         postTextarea.value = '';
  //     }
  // });

  function createNewPost(text) {
    const postsFeed = document.querySelector(".posts-feed");
    const newPost = document.createElement("div");
    newPost.className = "post-card";

    const currentDate = new Date();
    const timeString = "Just now";

    newPost.innerHTML = `
                <div class="post-header">
                <div class="user-avatar">J</div>
                <div class="post-info">
                      <div class="post-author">You</div>
                      <div class="post-meta">@yourhandle · ${timeString}</div>
                    </div>
                    <div class="post-menu">
                      <i class="fas fa-ellipsis-h"></i>
                    </div>
                  </div>
                  <div class="post-content">
                    <div class="post-text">${text}</div>
                  </div>
                  <div class="post-stats">
                    <div>0 comments</div>
                    <div>0 shares</div>
                  </div>
                  <div class="post-actions-bar">
                    <div class="action-button">
                    <i class="far fa-comment"></i>
                    <span>Comment</span>
                        </div>
                        <div class="action-button">
                    <i class="far fa-bookmark"></i>
                    <span>Bookmark</span>
                    </div>
                    <div class="action-button">
                    <i class="far fa-heart"></i>
                    <span>Like</span>
                    </div>
                    <div class="action-button">
                    <i class="far fa-share-square"></i>
                    <span>Share</span>
                    </div>
                </div>
                        `;

    // Insert the new post at the top of the feed, after the create-post element
    const createPostElement = document.querySelector(".create-post");
    postsFeed.insertBefore(newPost, createPostElement.nextSibling);

    // Add event listeners to the new post's action buttons
    const newActionButtons = newPost.querySelectorAll(".action-button");
    newActionButtons.forEach((button) => {
      button.addEventListener("click", function () {
        if (this.querySelector(".fa-heart")) {
          this.classList.toggle("liked");
          const icon = this.querySelector(".fa-heart");
          if (this.classList.contains("liked")) {
            icon.classList.remove("far");
            icon.classList.add("fas");
          } else {
            icon.classList.remove("fas");
            icon.classList.add("far");
          }
        }
      });
    });
  }
});

function toggleFollow(button, userId, targetUserId) {
  const isFollowing = $(button).text().trim().includes("Following"); // Check if currently following
  const requestType = isFollowing ? "unfollow" : "follow";

  // Make the AJAX request to toggle follow/unfollow
  $.ajax({
    url: "process/follow_action.php", // Adjust the URL if necessary
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      request: requestType,
      followerId: userId,
      followingId: targetUserId,
    }),
    success: function (response) {
      if (response.status === "success") {
        // Toggle button text and icon based on follow/unfollow status
        if (requestType === "follow") {
          // Change to Following with check icon
          $(button).html('<i class="fas fa-check"></i> Following');
        } else {
          // Change to Follow with plus icon
          $(button).html('<i class="fas fa-user-plus"></i> Follow');
        }
      } else {
        console.error(
          "Error message:",
          response.message || "Unknown error occurred"
        );
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error:", textStatus, errorThrown);
    },
  });
}

// like function
function toggleLike(button, feedId, userId) {
  var thumbsUpIcon = $(button).find("i"); // The <i> tag with the class indicating the like status
  var likeCountElement = $(button).find(".likeCount"); // The div where the like count is displayed

  var isLiked = thumbsUpIcon.hasClass("fa-solid");
  var requestType = isLiked ? "unlike" : "like";

  $.ajax({
    url: "/inc/php/handler.php",
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      request: requestType,
      userId: userId,
      feedId: feedId,
    }),
    success: function (response) {
      if (response.status === "success") {
        if (requestType === "like") {
          thumbsUpIcon.removeClass("fa-regular").addClass("fa-solid");
        } else {
          thumbsUpIcon.removeClass("fa-solid").addClass("fa-regular");
        }

        var updatedLikeCount =
          response.likeCount === null ? "" : response.likeCount;
        likeCountElement.text(updatedLikeCount);
      } else {
        console.error("Error message:", response.message);
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error:", textStatus, errorThrown);
    },
  });
}

// bookmark function
function toggleSave(button, id) {
  var thumbsUpIcon = $(button).find("i"); // The <i> tag with the class indicating the like status
  var likeCountElement = $(button).find(".likeCount"); // The div where the like count is displayed

  var isLiked = thumbsUpIcon.hasClass("fa-solid");
  var requestType = isLiked ? "unsave" : "save";
  $.ajax({
    url: "/inc/php/savePost.php", // The PHP page where you want to process the data
    type: "POST",
    data: {
      id: id,
      request: requestType,
    },
    success: function (response) {
      if (response.status === "success") {
        if (requestType === "save") {
          thumbsUpIcon.removeClass("fa-regular").addClass("fa-solid");
        } else {
          thumbsUpIcon.removeClass("fa-solid").addClass("fa-regular");
        }
        // var updatedLikeCount = response.likeCount === null ? '' : response.likeCount;
        // likeCountElement.text(updatedLikeCount);
      } else {
        console.error("Error message:", response.message);
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error:", textStatus, errorThrown);
    },
  });
}

// readmore toggle

function toggleReadMore(postId) {
  event.preventDefault(); // Prevent default action
  event.stopPropagation(); // Stop propagation as you had

  var $truncatedContent = $("#postContent_" + postId);
  var $fullContent = $("#fullContent_" + postId);
  var $button = $('button[data-id="' + postId + '"]'); // More specific selector

  if ($fullContent.is(":visible")) {
    // If full content is visible, switch to truncated
    $truncatedContent.show();
    $fullContent.hide();
    $button.text("Read More");
  } else {
    // If truncated content is visible, switch to full
    $truncatedContent.hide();
    $fullContent.show();
    $button.text("Read Less");
  }
}

// dropdown for the three dot
function toggleDropcardMenu(postId) {
  var $menu = $("#dropcardMenu_" + postId);
  var isVisible = $menu.hasClass("active");

  // Close any open dropdown menus before toggling the current one
  closeAllDropcardMenus();

  // Toggle display for the clicked menu
  if (!isVisible) {
    $menu.css("display", "block");
    // Use setTimeout to ensure display:block is applied before adding the active class
    setTimeout(function () {
      $menu.addClass("active");
    }, 10);
  } else {
    $menu.removeClass("active");
    // Wait for transition to complete before hiding
    setTimeout(function () {
      $menu.css("display", "none");
    }, 300); // Match this to the CSS transition time
  }
}

function closeAllDropcardMenus() {
  $(".card-dropdown-menu").each(function () {
    var $menu = $(this);
    if ($menu.hasClass("active")) {
      $menu.removeClass("active");
      setTimeout(function () {
        $menu.css("display", "none");
      }, 300); // Match this to the CSS transition time
    }
  });
}

// Close menus when clicking outside
$(document).on("click", function (e) {
  if (!$(e.target).closest(".post-menu, .card-dropdown-menu").length) {
    closeAllDropcardMenus();
  }
});

// Function to delete a post
function deletePost(postId) {
  if (confirm("Are you sure you want to delete this post?")) {
    $.ajax({
      url: "inc/php/edit_post.php",
      type: "POST",
      data: {
        action: "delete",
        post_id: postId,
      },
      success: function (response) {
        try {
          const result = response;
          if (result.status === "success") {
            alert(result.message);
            location.reload();
          } else {
            alert(result.message || "Failed to delete post.");
          }
        } catch (e) {
          alert("Invalid response received from server.");
        }
      },
      error: function (xhr, status, error) {
        alert("An error occurred while deleting the post: " + error);
      },
    });
  }
}

function editPost(postId) {
  // Get references to modal elements
  const modal = document.getElementById("editPostModal");
  const $modalTextarea = $("#modalContentTextarea");
  const $saveButton = $("#saveModalEditButton");
  const $closeButton = $(".close-edit-modal");
  const $cancelButton = $(".cancel-edit-btn");

  // Remove any existing click handlers to prevent duplicates
  $saveButton.off("click");
  $closeButton.off("click");
  $cancelButton.off("click");

  // Fetch the post content via AJAX
  $.ajax({
    url: "inc/php/getEditContent.php",
    type: "POST",
    data: {
      action: "getChat",
      post_id: postId,
    },
    success: function (response) {
      if (response.chat !== undefined) {
        // Populate the textarea with the retrieved chat content
        $modalTextarea.val(response.chat);

        // Set the postId in the save button's data attribute
        $saveButton.data("postId", postId);

        // Set cursor to the end of the text
        $modalTextarea.focus();
        setTimeout(() => {
          $modalTextarea[0].setSelectionRange(
            $modalTextarea.val().length,
            $modalTextarea.val().length
          );
        }, 100);

        // Open the modal
        modal.classList.add("active");
        document.body.style.overflow = "hidden"; // Prevent scrolling

        // Add click handler for the save button
        $saveButton.on("click", function () {
          const newContent = $modalTextarea.val();
          savePostEdit(postId, newContent);
        });

        // Add click handlers for closing the modal
        $closeButton.on("click", function () {
          closeEditModal();
        });

        $cancelButton.on("click", function () {
          closeEditModal();
        });
      } else {
        alert("Failed to retrieve chat content.");
      }
    },
    error: function () {
      alert("An error occurred while retrieving the post.");
    },
  });
}

// Function to close the edit modal
function closeEditModal() {
  const modal = document.getElementById("editPostModal");
  modal.classList.remove("active");
  document.body.style.overflow = ""; // Restore scrolling
}
function closeReportModal() {
  const modal = document.getElementById("reportModal");
  modal.classList.remove("active");
  document.body.style.overflow = "";
}

// Function to save the edited post content
function savePostEdit(postId, content) {
  $.ajax({
    url: "inc/php/edit_post.php",
    type: "POST",
    data: {
      action: "edit", // Changed from 'update' to 'edit' to match your first function
      post_id: postId,
      content: content,
    },
    success: function (response) {
      if (response.status === "success") {
        closeEditModal();
        alert(response.message);
        location.reload(); // Refresh to show the updated post
      } else {
        alert(response.message || "Failed to update post.");
      }
    },
    error: function () {
      alert("An error occurred while saving the post.");
    },
  });
}

// Event delegation for escape key to close modal
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeEditModal();
  }
});

// Click outside to close
document.addEventListener("click", function (event) {
  const modal = document.getElementById("editPostModal");
  const modalContent = modal.querySelector(".edit-post-modal-content");

  if (
    modal.classList.contains("active") &&
    !modalContent.contains(event.target)
  ) {
    closeEditModal();
  }
});

function uploadPost(schedule = false) {
  const content = document.getElementById("contentTextarea").value;
  const loadingIcon = document.getElementById("loadingIcon");

  loadingIcon.style.display = "block";

  // Create a FormData object to send data
  const formData = new FormData();

  // Conditionally append fields if they have values
  if (content) formData.append("content", content);

  // Append each file to the FormData object
  const fileInput = document.getElementById("fileInput");
  if (fileInput.files.length > 0) {
    for (let i = 0; i < fileInput.files.length; i++) {
      formData.append("media[]", fileInput.files[i]); // Use an array (media[]) to send multiple files
    }
  }
  if (!content.trim() && !fileInput) {
    alert("Please enter some content to share.");
    return;
  }

  // Get values from hidden fields
  const hiddenTitle = document.getElementById("hiddenTitle").value;
  const hiddenDesc = document.getElementById("hiddenDesc").value;
  const hiddenUrl = document.getElementById("hiddenUrl").value;
  const hiddenImage = document.getElementById("hiddenImage").value;
  const hiddenDomain = document.getElementById("hiddenDomain").value;
  const hiddenYTLink = document.getElementById("hiddenYTLink").value;
  var scheduleDate = $("#scheduleDate").val(); // Always get value

  // Get visibility from the dropdown button (what the user sees as selected).
  // Default is 'public' — only flips to 'private' when the friends icon is shown,
  // which is the explicit "People I follow" selection.
  var __visBtn = document.getElementById("privacy-dropdown-btn");
  const visibility =
    __visBtn && __visBtn.querySelector("i.fa-user-friends")
      ? "private"
      : "public";

  // Append hidden fields if they have values
  if (hiddenTitle) formData.append("hiddenTitle", hiddenTitle);
  if (hiddenDesc) formData.append("hiddenDesc", hiddenDesc);
  if (hiddenUrl) formData.append("hiddenUrl", hiddenUrl);
  if (hiddenImage) formData.append("hiddenImage", hiddenImage);
  if (hiddenDomain) formData.append("hiddenDomain", hiddenDomain);
  if (hiddenYTLink) formData.append("hiddenYTLink", hiddenYTLink);
  if (visibility) formData.append("visibility", visibility);
  // ✅ Scheduling logic
  if (schedule && scheduleDate) {
    formData.append("deleteFlag", "1"); // Use deleteFlag as scheduler indicator
    formData.append("scheduleDate", scheduleDate);
  } else {
    formData.append("deleteFlag", "0"); // Regular post
  }

  // Send the data to PHP via fetch API
  fetch("process_data.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      // Hide loading spinner after response
      loadingIcon.style.display = "none";

      if (data.status === "success") {
        // Close modal after successful submission
        toggleModal();

        // Show success message
        showToast(
          scheduleDate
            ? "Your post has been scheduled successfully!"
            : "Your post has been shared successfully!"
        );

        // Reload the page after a short delay
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((error) => {
      loadingIcon.style.display = "none";
      alert("An error occurred: " + error);
    });
}

window.addEventListener("load", function () {
  // Get the postId from the URL fragment (e.g., #post_3579)
  var postIdFromURL = window.location.hash.substring(1); // Get post_3579
  var postId = postIdFromURL.replace("post_", ""); // Remove 'post_' to get the actual post ID

  // Get the stored postId from localStorage
  var storedPostId = localStorage.getItem("scrollToPost");

  // Check if the postId exists and matches
  if (postId && postId === storedPostId) {
    // Scroll to the post after it is fully loaded
    scrollToPost(postId);
  }

  // Optionally, remove the postId from localStorage after the scroll action
  localStorage.removeItem("scrollToPost");
});

function scrollToPost(postId) {
  // Try to find the element by postId (e.g., post_3579)
  var postElement = document.getElementById("post_" + postId);

  if (!postElement) {
    // If the post is not yet available, wait for it to load
    setTimeout(function () {
      scrollToPost(postId); // Check again after 500ms
    }, 500);
  } else {
    // Scroll to the post with smooth behavior
    postElement.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }
}

function openModal(type, path, count, id) {
  let str = path;
  let mediaPaths = str.split(",");

  event.stopPropagation(); // Prevent event propagation

  if (type === "image") {
    // Convert the array of image paths to a string and encode it
    const imagePaths = encodeURIComponent(mediaPaths.join(","));
    const selectedImage = mediaPaths[count];

    // Redirect to a new page with all images and the selected image index
    window.location.href =
      "view_image.php?imagePaths=" +
      imagePaths +
      "&selectedImage=" +
      encodeURIComponent(selectedImage) +
      "&postId=" +
      id;
  }

  if (type === "video") {
    const videoSrc = mediaPaths[count];

    // Redirect to a new page with the selected video
    window.location.href =
      "view_image.php?videoPath=" +
      encodeURIComponent(videoSrc) +
      "&postId=" +
      id;
  }
}
function showToast(message) {
  // Remove any existing toast
  $(".toast-notification").remove();

  // Create a new toast element
  var toast = $('<div class="toast-notification">' + message + "</div>");

  // Append to body
  $("body").append(toast);

  // Show the toast with animation
  setTimeout(function () {
    toast.addClass("show");

    // Hide and remove the toast after 2 seconds
    setTimeout(function () {
      toast.removeClass("show");
      setTimeout(function () {
        toast.remove();
      }, 300);
    }, 2000);
  }, 10);
}

function shareNow(postId) {
  var url = "https://newsjunction.net/streamPush.php?id=" + postId;
  var shareText = "Check this out on News Junction";

  if (navigator.share) {
    navigator
      .share({ title: "News Junction", text: shareText, url: url })
      .catch(function (err) {
        if (err && err.name !== "AbortError") {
          openSharePopup(url, shareText);
        }
      });
    return;
  }

  openSharePopup(url, shareText);
}

function openSharePopup(url, shareText) {
  var existing = document.getElementById("njSharePopup");
  if (existing) existing.remove();

  var encURL = encodeURIComponent(url);
  var encMsg = encodeURIComponent(shareText + " " + url);

  var wrap = document.createElement("div");
  wrap.id = "njSharePopup";
  wrap.style.cssText =
    "position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;";
  wrap.innerHTML =
    '<div style="background:#fff;border-radius:12px;padding:20px;min-width:300px;max-width:90vw;box-shadow:0 10px 30px rgba(0,0,0,0.3);font-family:inherit;color:#111;">' +
    '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">' +
    "<strong>Share this post</strong>" +
    '<span id="njShareClose" style="cursor:pointer;font-size:24px;line-height:1;color:#666;">&times;</span>' +
    "</div>" +
    '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">' +
    '<a id="njShareWA" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#111;">' +
    '<i class="fab fa-whatsapp" style="color:#25D366;font-size:18px;"></i> WhatsApp</a>' +
    '<a id="njShareTW" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#111;">' +
    '<i class="fab fa-x-twitter" style="color:#111;font-size:18px;"></i> X (Twitter)</a>' +
    '<a id="njShareFB" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#111;">' +
    '<i class="fab fa-facebook" style="color:#1877F2;font-size:18px;"></i> Facebook</a>' +
    '<a id="njShareTG" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#111;">' +
    '<i class="fab fa-telegram" style="color:#0088cc;font-size:18px;"></i> Telegram</a>' +
    '<button id="njShareCopy" style="grid-column:1/-1;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;font:inherit;color:#111;">' +
    '<i class="fas fa-link" style="font-size:18px;"></i> Copy Link</button>' +
    "</div></div>";
  document.body.appendChild(wrap);

  document.getElementById("njShareWA").href = "https://wa.me/?text=" + encMsg;
  document.getElementById("njShareTW").href =
    "https://twitter.com/intent/tweet?text=" + encMsg;
  document.getElementById("njShareFB").href =
    "https://www.facebook.com/sharer/sharer.php?u=" + encURL;
  document.getElementById("njShareTG").href =
    "https://t.me/share/url?url=" + encURL + "&text=" + encodeURIComponent(shareText);

  document.getElementById("njShareClose").onclick = function () {
    wrap.remove();
  };
  document.getElementById("njShareCopy").onclick = function () {
    copyToClipboard(url);
    wrap.remove();
  };
  wrap.onclick = function (e) {
    if (e.target === wrap) wrap.remove();
  };
}

function copyToClipboard(note) {
  // Text to copy
  var textToCopy = note;

  // Try using the Clipboard API first
  if (navigator.clipboard) {
    navigator.clipboard
      .writeText(textToCopy)
      .then(function () {
        showToast("Link copied to clipboard");
      })
      .catch(function (error) {
        console.error("Clipboard API error: ", error);
        fallbackCopy(textToCopy);
      });
  } else {
    console.error("Clipboard API is not available");
    fallbackCopy(textToCopy);
  }

  // Fallback method using a temporary textarea element
  function fallbackCopy(textToCopy) {
    var $tempTextArea = $("<textarea>");
    $tempTextArea.val(textToCopy).appendTo("body");
    $tempTextArea.focus().select();
    $tempTextArea[0].setSelectionRange(0, textToCopy.length);

    try {
      var successful = document.execCommand("copy");
      if (successful) {
        showToast("Link copied to clipboard");
      } else {
        showToast("Failed to copy link");
      }
    } catch (err) {
      console.error("Error copying text: ", err);
      showToast("Failed to copy link");
    } finally {
      $tempTextArea.remove();
    }
  }
}

function hideChannelCards(id) {
  const overlay = document.getElementById(`channelOverlay_${id}`);
  const wrapper = document.getElementById(`channelCardsWrapper_${id}`);

  overlay.style.display = "none";
  wrapper.classList.remove("active");
}
$(document).ready(function () {
  $(document).on("click", ".follow-button", function () {
    const targetUserId = $(this).data("id"); // ID of the user to follow/unfollow
    toggleFollow(this, userId, targetUserId); // Pass `this` (the button element) as the first parameter
  });

  $(document).on("click", ".saveButton", function (e) {
    var id = $(this).data("id");
    toggleSave(this, id);
  });

  $(document).on("click", ".shareNow", function () {
    shareNow($(this).data("id"));
  });
});

$(document).ready(function () {
  // Target elements that have both fa-regular AND fa-thumbs-up classes
  $('body').on('click', '.likeButton', function (e) {
    // Check if it contains a thumbs-up icon
    const icon = $(this).find('i.fa-regular.fa-thumbs-up');

    if (icon.length === 0) return; // Exit if the icon is not found

    // Use the icon element as the position reference
    const target = icon;

    const emojis = ['👍'];
    const emojiCount = 8;

    for (let i = 0; i < emojiCount; i++) {
      const emoji = $('<span></span>');
      emoji.html(emojis[0]);
      emoji.addClass('flowing-emoji');

      const buttonOffset = target.offset();
      const startX = buttonOffset.left + target.width() / 2;
      const startY = buttonOffset.top;
      const randomOffsetX = (Math.random() - 0.5) * 20;

      emoji.css({
        'position': 'absolute',
        'left': `${startX + randomOffsetX}px`,
        'top': `${startY}px`,
        'font-size': `${1.2 + Math.random() * 0.4}rem`,
        'z-index': '999999',
        'pointer-events': 'none',
        'opacity': '1',
        'transform': `translate(-50%, 0%) rotate(${(Math.random() - 0.5) * 20}deg)`
      });

      $('body').append(emoji);

      const endX = startX + (Math.random() - 0.5) * 60;
      const endY = startY - (200 + Math.random() * 60);
      const delay = Math.random() * 200;
      const duration = 1000 + Math.random() * 500;

      setTimeout(function () {
        emoji.animate({
          top: `${endY}px`,
          left: `${endX}px`,
          opacity: 0
        }, {
          duration: duration,
          easing: 'easeOutQuad',
          step: function (now, fx) {
            if (fx.prop === "top") {
              const wiggle = Math.sin(now * 0.03) * 3;
              $(this).css('margin-left', wiggle + 'px');
            }
          },
          complete: function () {
            $(this).remove();
          }
        });
      }, delay);
    }
  });


  // Add CSS styles dynamically if not already there
  if ($('#emoji-flow-styles').length === 0) {
    $('head').append(`
            <style id="emoji-flow-styles">
                .flowing-emoji {
                    position: absolute;
                    pointer-events: none;
                    user-select: none;
                    z-index: 9999;
                    transition: transform 0.2s ease-out;
                }
            </style>
        `);
  }

  // Make sure jQuery easing is available
  if (typeof $.easing.easeOutQuad === 'undefined') {
    $.easing.easeOutQuad = function (x, t, b, c, d) {
      return -c * (t /= d) * (t - 2) + b;
    };
  }

  const notification = document.querySelector('.bookmarkNotification');

  $('body').on('click', '.saveButton', function (e) {
    const icon = $(this).find('i');

    if (icon.hasClass('fa-regular') && icon.hasClass('fa-bookmark')) {
      $('.bookmarkNotification').text('Bookmark Added');
    } else if (icon.hasClass('fa-solid') && icon.hasClass('fa-bookmark')) {
      $('.bookmarkNotification').text('Bookmark Removed');
    } else {
      return; // Exit if the correct icon isn't found
    }

    notification.classList.add('show');
    setTimeout(() => {
      notification.classList.remove('show');
    }, 1000);
  });
});