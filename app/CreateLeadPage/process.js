const chkFilterEmail =
  /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;

// Update field templates to include proper IDs
const fieldTemplates = {
  name: `
        <div class="form-group form-field" data-field="name">
            <label for="Name" >Name *</label>
            <input type="text" id="Name" name="Name" required>
        </div>
    `,
  institute: `
        <div class="form-group form-field" data-field="institute">
            <label for="Company">Institute/Organization *</label>
            <input type="text" id="Company" name="Company" required>
        </div>
    `,
  designation: `
        <div class="form-group form-field" data-field="designation">
            <label for="Designation">Designation *</label>
            <input type="text" id="Designation" name="Designation" required>
        </div>
    `,
  email: `
        <div class="form-group form-field" data-field="email">
            <label for="Email">Email *</label>
            <input type="email" id="Email" name="Email" required>
        </div>
    `,
  phone: `
        <div class="form-group form-field" data-field="phone">
            <label for="Mobile">Phone Number *</label>
            <input type="tel" id="Mobile" name="Mobile" required>
        </div>
    `,
  query: `
        <div class="form-group form-field" data-field="query">
        <button class="edit-button edit-control" onclick="editText('labelQuery')"><i class="fa fa-pencil-alt"></i>
                  </button>
            <label for="Query" id='labelQuery'>Name the Field</label>
            <textarea id="Query" name="Query" rows="4" required></textarea>
        </div>
    `,
  state: `
        <div class="form-group form-field" data-field="state">
            <label for="state">State *</label>
            <input type="text " id="state" name="state" rows="4" required>
        </div>
    `,
  city: `
        <div class="form-group form-field" data-field="city">
            <label for="State">City *</label>
            <input type="text " id="city" name="city" rows="4" required>
        </div>
    `,
};

// Function to update form fields based on checkbox selection
function updateFormFields() {
  const formFields = document.getElementById("formFields");
  formFields.innerHTML = "";

  const selectedFields = Array.from(
    document.querySelectorAll('input[name="fieldSelect"]:checked')
  ).map((checkbox) => checkbox.value);

  selectedFields.forEach((field) => {
    formFields.insertAdjacentHTML("beforeend", fieldTemplates[field]);
    // Trigger animation
    setTimeout(() => {
      const newField = formFields.querySelector(`[data-field="${field}"]`);
      newField.classList.add("visible");
    }, 50);
  });

  // Save selected fields to localStorage
  localStorage.setItem("selectedFields", JSON.stringify(selectedFields));
}

// Initialize form fields on page load
window.addEventListener("load", function () {
  const savedFields = localStorage.getItem("selectedFields");
  if (savedFields) {
    const fields = JSON.parse(savedFields);
    fields.forEach((field) => {
      const checkbox = document.querySelector(`input[value="${field}"]`);
      if (checkbox) checkbox.checked = true;
    });
    updateFormFields();
  }
});

// Add event listeners to checkboxes
document.querySelectorAll('input[name="fieldSelect"]').forEach((checkbox) => {
  checkbox.addEventListener("change", updateFormFields);
});

// Function to edit text content
function editText(elementId) {
  const element = document.getElementById(elementId);
  const currentText = element.innerText;
  const newText = prompt("Edit text:", currentText);

  if (newText !== null && newText.trim() !== "") {
    element.innerText = newText;
  }
}

// Form validation function
function chkLead() {
  const email = document.getElementById("Email");
  const statusPanel = document.getElementById("panelStatus");
  const submitButton = document.getElementById("btnSubmit");
  const contentLoader = document.getElementById("contentLoader");

  // Reset status
  statusPanel.innerHTML = "";
  statusPanel.style.backgroundColor = "";

  // Validate email if it exists in the form
  if (email && !chkFilterEmail.test(email.value)) {
    statusPanel.innerHTML = "Please enter a valid email address.";
    statusPanel.style.backgroundColor = "#ffdddd";
    return false;
  }

  // Form is valid, show loader
  submitButton.style.display = "none";
  contentLoader.style.display = "block";

  // Simulate form submission (replace with actual form submission logic)
  setTimeout(() => {
    statusPanel.innerHTML = "Form submitted successfully!";
    statusPanel.style.backgroundColor = "#ddffdd";
    submitButton.style.display = "block";
    contentLoader.style.display = "none";
  }, 1500);

  return false; // Prevent actual form submission for this demo
}

$(document).ready(function () {
  // Loop through all elements with class 'template'
  $(".template").each(function () {
    $(this).on("click", function () {
      // Get the category of the clicked template
      const category = $(this).data("category"); // Category from data-category attribute

      // Create the URL of the PHP endpoint for category-based fetching
      const templateUrl = `load_template.php?category=${category}`;

      // Create a new div element for the template content
      const newDiv = $('<div class="new-div"></div>');

      // Show loading message while fetching
      newDiv.html("<p>Loading templates...</p>");

      // Append the new div to the new-div-container
      $("#new-div-container").empty().append(newDiv); // Clear previous content before appending

      // Fetch the template data from the server using the PHP endpoint
      $.ajax({
        url: templateUrl, // The PHP endpoint
        method: "GET", // HTTP method to use
        success: function (response) {
          // Check if response contains templates (using data-template-path)
          if (response.includes("data-template-path")) {
            // Parse the response and display the templates
            const templatesContainer = $(response); // Assuming multiple templates are returned

            // Insert each template into the new div
            newDiv.html(templatesContainer);

            // Add click event for each template
            newDiv.find(".template").each(function () {
              $(this).on("click", function () {
                // Get the template's path and image details
                const templatePath = $(this).data("template-path");
                const imagePath = $(this).find("img").attr("src");

                // Handle template selection: Update canvas area
                const canvasArea = $("#canvasArea");
                canvasArea.empty(); // Clear the current content

                // Fetch and display the template content in the canvas area
                $.ajax({
                  url: templatePath,
                  method: "GET",
                  success: function (templateContent) {
                    // Insert the template content into the canvas area
                    canvasArea.html(templateContent);

                    // Make all text elements editable
                    makeElementsEditable();

                    // Set up image uploads
                    setupImageUploads();

                    // Add save button functionality
                    addSaveButton(templatePath);
                  },
                  error: function (xhr, status, error) {
                    canvasArea.html(`<p>Error loading template: ${error}</p>`);
                  },
                });
              });
            });
          } else {
            // Handle error if no templates are returned
            newDiv.html("<p>No templates found for this category.</p>");
          }
        },
        error: function (xhr, status, error) {
          // If there's an error (e.g., file not found), show an error message
          newDiv.html(`<p>Error: ${error}</p>`);
        },
      });
    });
  });
});

// Function to show the modal
function showModal() {
  var modal = document.getElementById("myModal");
  modal.style.display = "block"; // Show the modal
}

// Function to close the modal
function closeModal() {
  var modal = document.getElementById("myModal");
  modal.style.display = "none"; // Hide the modal
}

// Close the modal when the <span> (close button) is clicked
document
  .getElementsByClassName("close")[0]
  .addEventListener("click", closeModal);

// Close the modal if the user clicks outside the modal content
window.addEventListener("click", function (event) {
  var modal = document.getElementById("myModal");
  if (event.target === modal) {
    closeModal();
  }
});

document.addEventListener("DOMContentLoaded", function () {
  let selectedElement = null;
  let textCounter = 0;

  // Function to create a new text element
  function createTextElement() {
    const textElement = document.createElement("div");
    textElement.className = "text-element";
    textElement.setAttribute("data-text-id", `text-${++textCounter}`);
    textElement.innerHTML = "Click to edit text";
    textElement.style.left = "50px";
    textElement.style.top = "50px";

    return textElement;
  }

  // Add text button click handler
  document.getElementById("addTextBtn").addEventListener("click", function () {
    const textElement = createTextElement();
    document.getElementById("canvasArea").appendChild(textElement);
    makeElementDraggable(textElement);
    selectElement(textElement);
  });

  // Utility function to convert RGB to HEX
  function rgb2hex(rgb) {
    if (!rgb) return "#000000";
    if (rgb.search("rgb") == -1) return rgb;
    rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)$/);

    function hex(x) {
      return ("0" + parseInt(x).toString(16)).slice(-2);
    }
    return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
  }

  // Click outside to deselect
  document.addEventListener("click", function (e) {
    if (
      !e.target.classList.contains("text-element") &&
      !e.target.closest(".text-controls")
    ) {
      if (selectedElement) {
        selectedElement.classList.remove("selected");
        selectedElement.setAttribute("contenteditable", "false");
        selectedElement = null;
      }
    }
  });

  // Function to save the current state of all text elements
  function saveTemplate(templatePath) {
    const elements = document.querySelectorAll(".text-element");
    const templateData = [];

    elements.forEach((element) => {
      const styles = getComputedStyle(element);
      templateData.push({
        id: element.getAttribute("data-text-id"),
        text: element.innerHTML,
        position: {
          left: styles.left,
          top: styles.top,
        },
        width: styles.width,
        height: styles.height,
        fontFamily: styles.fontFamily,
        fontSize: styles.fontSize,
        color: rgb2hex(styles.color),
        textAlign: styles.textAlign,
        fontWeight: styles.fontWeight,
        fontStyle: styles.fontStyle,
        textDecoration: styles.textDecoration,
      });
    });

    const jsonData = JSON.stringify(templateData);

    // Save data logic here
    console.log("Saving Template:", jsonData);
    // You can send the jsonData to the server or save it locally
  }

  // Add save button
  // function addSaveButton(templatePath) {
  //   const saveBtn = $(
  //     '<button  class="image-upload-btn" id="saveTemplate">Save Changes</button>'
  //   )
  //     .prependTo("#canvasArea")
  //     .css({
  //       position: "fixed",
  //       top: "20px",
  //       right: "20px",
  //       background: "#28a745",
  //       color: "white",
  //       border: "none",
  //       padding: "10px 20px",
  //       cursor: "pointer",
  //       "z-index": "1000",
  //     });

  //   saveBtn.click(function () {
  //     saveTemplate(templatePath);
  //   });
  // }

  // Example: Initialize save button with templatePath
  addSaveButton("/path/to/save/template");
});

// Function to make elements editable and add action icons
function makeElementsEditable() {
  // Make various elements editable
  $(
    "#canvasArea p, #canvasArea h1, #canvasArea h2, #canvasArea h3, #canvasArea h4, #canvasArea h5, #canvasArea h6, #canvasArea span, #canvasArea a, #canvasArea li a, #canvasArea strong, #canvasArea em, #canvasArea b, #canvasArea i"
  )
    .attr("contenteditable", "true") // Make elements editable
    .addClass("editable-element") // Add a class to identify editable elements
    .css({
      position: "relative", // For positioning the action icons only
    })
    .hover(
      function () {
        // Add a dashed border without changing any other styles
        var originalBorder = $(this).css("border");
        $(this).data("original-border", originalBorder);

        // Remove any existing action icons first to prevent duplicates
        $(".edit-actions").remove();

        // Create action icons container
        var $actionsContainer = $('<div class="edit-actions"></div>').css({
          position: "absolute",
          top: "30px",
          right: "0",
          "background-color": "#f8f9fa",
          border: "1px solid #ddd",
          "border-radius": "4px",
          padding: "5px 8px",
          "box-shadow": "0 2px 5px rgba(0,0,0,0.1)",
          "z-index": "1000",
          display: "flex",
          gap: "10px",
        });

        // Add edit text icon with tooltip
        var $editTextBtn = $(
          '<span class="edit-action edit-text-btn" title="Edit Text"></span>'
        )
          .html('<i class="fas fa-edit"></i>')
          .css({
            cursor: "pointer",
            color: "#007bff",
            padding: "2px",
          });

        // Add link icon with tooltip
        var $addLinkBtn = $(
          '<span class="edit-action add-link-btn" title="Add/Edit Link"></span>'
        )
          .html('<i class="fas fa-link"></i>')
          .css({
            cursor: "pointer",
            color: "#007bff",
            padding: "2px",
          });

        // Append buttons to container
        $actionsContainer.append($editTextBtn).append($addLinkBtn);

        // Append container to element
        $(this).append($actionsContainer);
      },
      function () {
        // Restore original border
        $(this).css("border", $(this).data("original-border"));

        // Hide action icons immediately if not hovering over them
        if (!$(".edit-actions:hover").length) {
          $(".edit-actions").remove();
        }
      }
    );

  // Add event handler for the action icons so they don't disappear immediately when hovered
  $(document).on("mouseleave", ".edit-actions", function () {
    $(this).remove();
  });

  // Store the original HTML structure before editing begins
  $(document).on("focus", ".editable-element", function () {
    $(this).data("original-structure", $(this).html());
  });

  // Preserve nested tags on blur (when editing is complete)
  $(document).on("blur", ".editable-element", function () {
    let $this = $(this);

    // For elements that have nested <a> tags
    if ($this.find("a").length === 0 && $this.data("had-links")) {
      // The a tag was removed during editing
      // Restore from our backup but keep the new text
      let newText = $this.text();
      let originalStructure = $this.data("original-structure");

      // Create a temporary element to work with the structure
      let $temp = $("<div>").html(originalStructure);

      // If there was just one link that wrapped all content
      if ($temp.children("a").length === 1 && $temp.children().length === 1) {
        $temp.find("a").text(newText);
        $this.html($temp.html());
      }
      // If we had mixed content or multiple links, do our best to preserve structure
      else {
        // More complex case - would need advanced handling
        // For now, we'll just restore the original with new text in the first child
        if ($temp.find("a").length > 0) {
          $temp.find("a").first().text(newText);
          $this.html($temp.html());
        }
      }
    }

    // Check for other tags like strong, em, b, i
    ["strong", "em", "b", "i"].forEach(function (tag) {
      if ($this.find(tag).length === 0 && $this.data("had-" + tag)) {
        // Similar restoration logic for other tags
        let newText = $this.text();
        let originalStructure = $this.data("original-structure");

        let $temp = $("<div>").html(originalStructure);
        if ($temp.children(tag).length === 1 && $temp.children().length === 1) {
          $temp.find(tag).text(newText);
          $this.html($temp.html());
        }
      }
    });
  });

  // Track what tags were present before editing
  $(document).on("mousedown", ".editable-element", function () {
    let $this = $(this);
    $this.data("had-links", $this.find("a").length > 0);
    $this.data("had-strong", $this.find("strong").length > 0);
    $this.data("had-em", $this.find("em").length > 0);
    $this.data("had-b", $this.find("b").length > 0);
    $this.data("had-i", $this.find("i").length > 0);
  });
}

// Modified link handling to work with contenteditable
$(document).ready(function () {
  // Use event delegation and check for contenteditable state
  $("#canvasArea").on("click", "a", function (event) {
    let $this = $(this);

    // Only prevent default and show prompt if we're not in editing mode
    if (!$this.is(":focus") && $this.attr("contenteditable") !== "true") {
      event.preventDefault(); // Prevent default link behavior

      let currentHref = $this.attr("href") || ""; // Get current href
      let newHref = prompt("Enter new link:", currentHref); // Prompt user for new link

      if (newHref !== null && newHref.trim() !== "") {
        $this.attr("href", newHref); // Update the href attribute
      }
    }
  });

  // Initialize the editable elements
  makeElementsEditable();

  // Rest of your existing document.ready code...
});

$(document).ready(function () {
  // Add modal HTML to the body
  $("body").append(`
<div id="myModal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%;  border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
<span class="close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
<h2>My Collection</h2>
<div id="modalContent" style="margin-top: 15px; max-height: 400px; overflow-y: auto;">
Loading...
</div>
<div style="margin-top: 15px; text-align: right;">
        <button id="insertBtn" style="padding: 8px 16px; color: white; border: none; border-radius: 4px; cursor: pointer;">Insert Selected</button>
</div>
</div>
</div>
`);

  // Initialize the editable elements
  makeElementsEditable();

  // Handle click on edit text button
  $(document).on("click", ".edit-text-btn", function (e) {
    e.stopPropagation();

    // Get the parent editable element
    var $element = $(this).closest(".editable-element");

    // Hide the action icons immediately
    $(".edit-actions").remove();

    // Focus on the parent editable element
    $element.focus();
  });

  // Handle click on add link button

  $(document).ready(function () {
    $("#canvasArea").on("click", "a", function (event) {
      event.preventDefault(); // Prevent default link behavior

      let currentHref = $(this).attr("href") || ""; // Get current href
      let newHref = prompt("Enter new link:(including https://)", currentHref); // Prompt user for new link

      if (newHref !== null && newHref.trim() !== "") {
        $(this).attr("href", newHref); // Update the href attribute
      }
    });
  });
});

// Add Font Awesome if not already included
if (
  $('link[href*="font-awesome"]').length === 0 &&
  $('script[src*="font-awesome"]').length === 0
) {
  $("head").append(
    '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">'
  );
}

function setupImageUploads() {
  $("#canvasArea img").each(function () {
    const img = $(this);
    const uploadBtn = $(
      '<input type="file" accept="image/*" style="display: none;">'
    ).insertAfter(img);

    const changeBtn = $(
      '<button class="image-upload-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97L9 12v3h3zM16 5l3 3"/></g></svg></button>'
    )
      .insertAfter(img)
      .css({
        position: "absolute",
        background: "#007bff",
        color: "white",
        border: "none",
        padding: "5px 10px",
        cursor: "pointer",
        "z-index": "400",
        // 'display': 'none'
      });

    changeBtn.click(function () {
      uploadBtn.click();
    });

    uploadBtn.change(function (e) {
      const file = e.target.files[0];
      if (file) {
        const formData = new FormData();
        formData.append("image", file);

        $.ajax({
          url: "upload.php", // PHP script to handle file upload
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          success: function (response) {
            if (response.startsWith("uploads/NW_images/")) {
              let tempSrc = response; // Temporary src while uploading
              img.attr("src", tempSrc);

              // After saving, update src by removing "uploads/"
              // let finalSrc = response.replace("uploads/", "");
              // setTimeout(function() {
              //     img.attr('src', finalSrc);
              // },2000);
            } else {
              alert("Error: " + response);
            }
          },
          error: function () {
            alert("Image upload failed.");
          },
        });
      }
    });
  });
}

// Function to add save button to the canvas area
function addSaveButton() {
  // Remove any existing save button first
  $("#saveTemplate").remove();

  // Create a new save button with proper styling
  const saveBtn = $(
    '<button id="saveTemplate" class="btn btn-primary"></button>'
  )
    .html('<i class="fas fa-save"></i> Save Page')
    .css({
      position: "fixed",
      top: "100px",
      right: "20px",
      "z-index": 1000,
      padding: "10px 15px",
      "background-color": "#28a745",
      color: "white",
      border: "none",
      "border-radius": "4px",
      "box-shadow": "0 2px 5px rgba(0,0,0,0.2)",
      cursor: "pointer",
    });

  // Add hover effect
  saveBtn.hover(
    function () {
      $(this).css("background-color", "#218838");
    },
    function () {
      $(this).css("background-color", "#28a745");
    }
  );

  // Add click event handler
  saveBtn.on("click", function (e) {
    e.preventDefault();
    saveTemplate();
  });

  // Add the button to the body
  $("body").append(saveBtn);

  console.log("Save button added to editor interface");
}

function saveTemplate() {
  // Create modal HTML structure
  const modalHTML = `
<div id="saveTemplateModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
<div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 5px;">
<h3>Save Template</h3>
<div style="margin-bottom: 15px;">
  <label for="modal-template-name">Template Name:</label>
  <input type="text" id="modal-template-name" style="width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box;">
</div>
<div style="margin-bottom: 15px;">
  <label for="modal-template-email">Notification Receiver Email:</label>
  <input type="email" id="modal-template-email" style="width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box;">
</div>
<div style="display: flex; justify-content: flex-end; margin-top: 20px;">
  <button id="modal-cancel-btn" style="padding: 8px 15px; margin-right: 10px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
  <button id="modal-save-btn" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Save</button>
</div>
</div>
</div>
`;

  // Add modal to body if it doesn't exist
  if (!$("#saveTemplateModal").length) {
    $("body").append(modalHTML);
  } else {
    // Reset the modal if it already exists
    $("#saveTemplateModal").css("display", "block");
    $("#modal-template-name").val("");
    $("#modal-template-email").val("");
  }

  // Show modal
  $("#saveTemplateModal").css("display", "block");

  // Remove any existing event handlers to prevent duplicates
  $("#modal-cancel-btn").off("click");
  $("#modal-save-btn").off("click");

  // Handle cancel button click
  $("#modal-cancel-btn").on("click", function () {
    $("#saveTemplateModal").css("display", "none");
  });

  // Handle save button click
  $("#modal-save-btn").on("click", function () {
    const templateName = $("#modal-template-name").val();
    const templateEmail = $("#modal-template-email").val();

    // Close the modal
    $("#saveTemplateModal").css("display", "none");

    // If user leaves name empty, abort save
    if (!templateName) {
      return;
    }

    // Create the template path using the provided name
    // Assuming templates are stored in a 'templates' directory and using .html extension
    const templatePath = `templates/${templateName}.html`;

    // Create FormData to handle both text content and images
    const formData = new FormData();

    // Get the current state of the template
    const template = $("#canvasArea").clone();

    // Remove editing-related elements and classes
    template.find('.field-selection, input[type="file"]').remove();
    template.find('.image-upload-btn, input[type="file"]').remove();
    template.find("[contenteditable]").removeAttr("contenteditable");
    template.find(".editable-element").removeClass("editable-element");

    // Modify image src paths
    template.find("img").each(function () {
      let src = $(this).attr("src");
      if (src && src.startsWith("uploads/")) {
        $(this).attr(
          "src",
          src.replace(
            "uploads/",
            "https://newsjunction.net/CreateLeadPage/uploads/"
          )
        );
      }
    });

    // Add the template path and HTML content to FormData
    formData.append("templatePath", templatePath);
    formData.append("template", template.html());
    formData.append("templateFileName", `${templateName}.html`);
    formData.append("templateEmail", `${templateEmail}`);

    // Add images to FormData with proper handling
    template.find("img").each(function (index) {
      const newImage = $(this).data("newImage");
      if (newImage instanceof File) {
        formData.append(`image_${index}`, newImage);
        // Store the full image element path for accurate selection
        const selector =
          $(this).prop("tagName").toLowerCase() +
          ($(this).attr("class")
            ? "." + $(this).attr("class").replace(/\s+/g, ".")
            : "") +
          ($(this).attr("id") ? "#" + $(this).attr("id") : "");
        formData.append(`image_${index}_selector`, selector);
      }
    });

    // Debug logging
    console.log("Sending template save request:", {
      templatePath,
      templateName,
      formDataEntries: Array.from(formData.entries()).map(([key, value]) =>
        value instanceof File
          ? `${key}: File(${value.name})`
          : `${key}: ${value}`
      ),
    });

    // Save the template
    $.ajax({
      url: "save-template.php",
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log("Save response:", response);
        if (response.success) {
          alert("Template saved successfully!");
          window.location.href = "savedPages.php";
        } else {
          alert(
            "Error saving template: " + (response.error || "Unknown error")
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Save error:", {
          status,
          error,
          response: xhr.responseText,
        });
        try {
          const response = JSON.parse(xhr.responseText);
          alert("Error saving template: " + (response.error || error));
        } catch (e) {
          alert("Error saving template: " + error);
        }
      },
    });
  });
}
// Helper function to get unique selector for an element
$.fn.getPath = function () {
  if (this.length != 1) return false;
  var path = this.prop("tagName").toLowerCase();
  if (this.attr("id")) {
    path += "#" + this.attr("id");
  } else if (this.attr("class")) {
    path += "." + this.attr("class").replace(/\s+/g, ".");
  }
  return path;
};

$(document).ready(function () {
  // Get the template parameter from URL
  const urlParams = new URLSearchParams(window.location.search);
  const templateParam = urlParams.get("template");

  // If template parameter exists, load the template
  if (templateParam) {
    loadTemplate(templateParam);
  }
});

function loadTemplate(templateParam) {
  // Show loading indicator
  $("#canvasArea").html(
    '<div style="text-align:center;padding:50px;"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Loading template...</p></div>'
  );

  // Perform AJAX request to load the template
  $.ajax({
    url: "edit_template.php",
    type: "GET",
    data: {
      template: templateParam,
    },
    success: function (response) {
      // Insert the response into the canvas area
      $("#canvasArea").html(response);

      // Make all text elements editable
      makeElementsEditable();

      // Set up image uploads
      setupImageUploads();

      // Add save button functionality (no parameter needed)
      addSaveButton();
    },
    error: function (xhr, status, error) {
      // Show error message if request fails
      $("#canvasArea").html(
        '<div class="alert alert-danger">Error loading template: ' +
          error +
          "</div>"
      );
      console.error("Error loading template:", error);
    },
  });
}
