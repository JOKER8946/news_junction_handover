    
$(document).ready(function() {
  // Loop through all elements with class 'template'
            $('.template').each(function() {
                $(this).on('click', function() {
      // Get the category of the clicked template
                    const category = $(this).data('category'); // Category from data-category attribute

      // Create the URL of the PHP endpoint for category-based fetching
      const templateUrl = `load_template.php?category=${category}`;

      // Create a new div element for the template content
      const newDiv = $('<div class="new-div"></div>');

      // Show loading message while fetching
                    newDiv.html('<p>Loading templates...</p>');

      // Append the new div to the new-div-container
                    $('#new-div-container').empty().append(newDiv); // Clear previous content before appending

      // Fetch the template data from the server using the PHP endpoint
      $.ajax({
        url: templateUrl, // The PHP endpoint
                        method: 'GET', // HTTP method to use
                        success: function(response) {
          // Check if response contains templates (using data-template-path)
                            if (response.includes('data-template-path')) {
            // Parse the response and display the templates
            const templatesContainer = $(response); // Assuming multiple templates are returned

            // Insert each template into the new div
            newDiv.html(templatesContainer);

            // Add click event for each template
                                newDiv.find('.template').each(function() {
                                    $(this).on('click', function() {
                // Get the template's path and image details
                                        const templatePath = $(this).data('template-path');
                                        const imagePath = $(this).find('img').attr('src');

                // Handle template selection: Update canvas area
                                        const canvasArea = $('#canvasArea');
                canvasArea.empty(); // Clear the current content

                // Fetch and display the template content in the canvas area
                $.ajax({
                  url: templatePath,
                                            method: 'GET',
                                            success: function(templateContent) {
                    // Insert the template content into the canvas area
                    canvasArea.html(templateContent);

                    // Make all text elements editable
                    makeElementsEditable();

                    // Set up image uploads
                    setupImageUploads();

                    // Add save button functionality
                    addSaveButton(templatePath);
                  },
                                            error: function(xhr, status, error) {
                    canvasArea.html(`<p>Error loading template: ${error}</p>`);
                                            }
                });
              });
            });
          } else {
            // Handle error if no templates are returned
                                newDiv.html('<p>No templates found for this category.</p>');
          }
        },
                        error: function(xhr, status, error) {
          // If there's an error (e.g., file not found), show an error message
          newDiv.html(`<p>Error: ${error}</p>`);
                        }
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



// Get the button that triggers the modal
var collectionBtn = document.getElementById("collectionBtn");

// Add click event listener to the "Collection" button
collectionBtn.addEventListener("click", function() {
  fetchData(); // Fetch data when the button is clicked
  showModal(); // Show the modal
});

// Close the modal when the <span> (close button) is clicked
        document.getElementsByClassName("close")[0].addEventListener("click", closeModal);

// Close the modal if the user clicks outside the modal content
window.addEventListener("click", function(event) {
  var modal = document.getElementById("myModal");
  if (event.target === modal) {
    closeModal();
  }
});



    
        document.addEventListener('DOMContentLoaded', function() {
  let selectedElement = null;
  let textCounter = 0;

  // Function to create a new text element
  function createTextElement() {
                const textElement = document.createElement('div');
                textElement.className = 'text-element';
                textElement.setAttribute('data-text-id', `text-${++textCounter}`);
                textElement.innerHTML = 'Click to edit text';
                textElement.style.left = '50px';
                textElement.style.top = '50px';

    return textElement;
  }

  // Add text button click handler
            document.getElementById('addTextBtn').addEventListener('click', function() {
    const textElement = createTextElement();
                document.getElementById('canvasArea').appendChild(textElement);
    makeElementDraggable(textElement);
    selectElement(textElement);
  });







  // Utility function to convert RGB to HEX
  function rgb2hex(rgb) {
                if (!rgb) return '#000000';
    if (rgb.search("rgb") == -1) return rgb;
    rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)$/);

    function hex(x) {
      return ("0" + parseInt(x).toString(16)).slice(-2);
    }
    return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
  }

  // Click outside to deselect
            document.addEventListener('click', function(e) {
                if (!e.target.classList.contains('text-element') && !e.target.closest('.text-controls')) {
      if (selectedElement) {
                        selectedElement.classList.remove('selected');
                        selectedElement.setAttribute('contenteditable', 'false');
        selectedElement = null;
      }
    }
  });

  // Function to save the current state of all text elements
  function saveTemplate(templatePath) {
                const elements = document.querySelectorAll('.text-element');
    const templateData = [];

                elements.forEach(element => {
      const styles = getComputedStyle(element);
      templateData.push({
                        id: element.getAttribute('data-text-id'),
        text: element.innerHTML,
        position: {
          left: styles.left,
                            top: styles.top
        },
        width: styles.width,
        height: styles.height,
        fontFamily: styles.fontFamily,
        fontSize: styles.fontSize,
        color: rgb2hex(styles.color),
        textAlign: styles.textAlign,
        fontWeight: styles.fontWeight,
        fontStyle: styles.fontStyle,
                        textDecoration: styles.textDecoration
      });
    });

    const jsonData = JSON.stringify(templateData);

    // Save data logic here
                console.log('Saving Template:', jsonData);
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
            addSaveButton('/path/to/save/template');
});
    


// Function to make elements editable and add action icons
function makeElementsEditable() {
  // Make various elements editable
            $('#canvasArea p, #canvasArea h1, #canvasArea h2, #canvasArea h3, #canvasArea h4, #canvasArea h5, #canvasArea h6, #canvasArea span, #canvasArea a, #canvasArea li, #canvasArea strong, #canvasArea em, #canvasArea b, #canvasArea i')
                .attr('contenteditable', 'true') // Make elements editable
                .addClass('editable-element') // Add a class to identify editable elements
    .css({
                    'position': 'relative' // For positioning the action icons only
    })
    .hover(
                    function() {
        // Add a dashed border without changing any other styles
                        var originalBorder = $(this).css('border');
                        $(this).data('original-border', originalBorder);


        // Remove any existing action icons first to prevent duplicates
                        $('.edit-actions').remove();

        // Create action icons container
        var $actionsContainer = $('<div class="edit-actions"></div>').css({
                            'position': 'absolute',
                            'top': '-30px',
                            'right': '0',
                            'background-color': '#f8f9fa',
                            'border': '1px solid #ddd',
                            'border-radius': '4px',
                            'padding': '5px 8px',
                            'box-shadow': '0 2px 5px rgba(0,0,0,0.1)',
                            'z-index': '1000',
                            'display': 'flex',
                            'gap': '10px'
        });

        // Add edit text icon with tooltip
                        var $editTextBtn = $('<span class="edit-action edit-text-btn" title="Edit Text"></span>').html('<i class="fas fa-edit"></i>')
          .css({
                                'cursor': 'pointer',
                                'color': '#007bff',
                                'padding': '2px'
          });

        // Add link icon with tooltip
                        var $addLinkBtn = $('<span class="edit-action add-link-btn" title="Add/Edit Link"></span>').html('<i class="fas fa-link"></i>')
          .css({
                                'cursor': 'pointer',
                                'color': '#007bff',
                                'padding': '2px'
          });

        // Add My Collection button with tooltip
                        var $myCollectionBtn = $('<span class="edit-action my-collection-btn" title="My Collection" id="collectionBtn"></span>').html('<i class="fas fa-folder"></i>')
          .css({
                                'cursor': 'pointer',
                                'color': '#007bff',
                                'padding': '2px'
          });

        // Append buttons to container
                        $actionsContainer.append($editTextBtn).append($addLinkBtn).append($myCollectionBtn);

        // Append container to element
        $(this).append($actionsContainer);
      },
                    function() {
        // Restore original border
                        $(this).css('border', $(this).data('original-border'));

        // Hide action icons immediately if not hovering over them
                        if (!$('.edit-actions:hover').length) {
                            $('.edit-actions').remove();
        }
      }
                )


  // Add event handler for the action icons so they don't disappear immediately when hovered
            $(document).on('mouseleave', '.edit-actions', function() {
    $(this).remove();
  });
}

        $(document).ready(function() {
  // Add modal HTML to the body
            $('body').append(`
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
            $(document).on('click', '.edit-text-btn', function(e) {
    e.stopPropagation();

    // Get the parent editable element
                var $element = $(this).closest('.editable-element');

    // Hide the action icons immediately
                $('.edit-actions').remove();

    // Focus on the parent editable element
    $element.focus();
  });


  // Handle click on add link button

            $(document).ready(function() {
                $("#canvasArea").on("click", "a", function(event) {
        event.preventDefault(); // Prevent default link behavior

        let currentHref = $(this).attr("href") || ""; // Get current href
        let newHref = prompt("Enter new link:", currentHref); // Prompt user for new link

        if (newHref !== null && newHref.trim() !== "") {
            $(this).attr("href", newHref); // Update the href attribute
        }
    });
});

  // Handle click on my collection button
            $(document).on('click', '.my-collection-btn', function(e) {
    e.stopPropagation();

    // Get the parent editable element for later reference
                var $element = $(this).closest('.editable-element');

    // Store the current element for later use when inserting content
                $('#myModal').data('currentElement', $element);

    // Hide the action icons immediately
                $('.edit-actions').remove();

    // Run the collection script
    fetchData();
    showModal();
  });

  // Add Insert Selected button functionality
            $(document).on('click', '#insertBtn', function() {
    var selectedIds = [];
                $('input[name="item"]:checked').each(function() {
      selectedIds.push($(this).val());
    });

    if (selectedIds.length > 0) {
      // Get the current element where we want to insert the content
                    var $currentElement = $('#myModal').data('currentElement');

      // For demonstration, we'll just show a message
      // In a real implementation, you would fetch the content for these IDs
      // and insert it into the current element
                    showFeedback($currentElement, selectedIds.length + " item(s) will be inserted");

      // Close the modal
      closeModal();
    } else {
      alert("Please select at least one item.");
    }
  });



  // Function to show feedback tooltip
  function showFeedback($element, message) {
    // Create feedback tooltip
    var $feedback = $('<div class="edit-feedback"></div>').text(message).css({
                    'position': 'absolute',
                    'top': '-30px',
                    'right': '0',
                    'background-color': '#28a745',
                    'color': 'white',
                    'border-radius': '4px',
                    'padding': '3px 8px',
                    'font-size': '12px',
                    'opacity': '0',
                    'transition': 'opacity 0.3s ease-in-out'
    });

    // Position relative to element
                $element.css('position', 'relative').append($feedback);

    // Show and hide the feedback
                $feedback.css('opacity', '1');

                setTimeout(function() {
                    $feedback.css('opacity', '0');

                    setTimeout(function() {
        $feedback.remove();
      }, 300);
    }, 1500);
  }

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


  // Modified fetchData function to create clickable items instead of checkboxes
  function fetchData() {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_data.php", true);
                xhr.onreadystatechange = function() {
      if (xhr.readyState == 4 && xhr.status == 200) {
        var data = JSON.parse(xhr.responseText);

        // Check if there's data
        if (data.message) {
          document.getElementById("modalContent").innerHTML = data.message;
        } else {
          // Initialize the content for clickable items
          var content = "";

          // Function to limit text to a specific number of words
          function limitText(text, wordLimit) {
                                var words = text.split(' ');
            if (words.length > wordLimit) {
                                    return words.slice(0, wordLimit).join(' ') + '...';
            } else {
              return text;
            }
          }

          // Function to strip HTML tags
          function stripHtml(html) {
            // Create a temporary div element
                                var temp = document.createElement('div');
            // Set the HTML content
            temp.innerHTML = html;
            // Return just the text content
                                return temp.textContent || temp.innerText || '';
          }

                            data.forEach(function(item) {
            // Strip HTML from title and limit
            var cleanTitle = stripHtml(item.title);
            var limitedTitle = limitText(cleanTitle, 10);

            // Encode content to avoid breaking HTML
            var encodedTitle = encodeURIComponent(cleanTitle);

            // Create a clickable item div for the heading
            content += `
            <div class="collection-item" data-content="${encodedTitle}" data-type="heading">
                <div class="item-preview">
                <strong>Heading:</strong> ${limitedTitle}
                </div>
            </div>`;

            // For images, use a text placeholder instead of the image HTML
            if (item.image) {
              content += `
            <div class="collection-item" data-content="${encodeURIComponent('[Image: ' + cleanTitle + ']')}" data-type="image">
                <div class="item-preview">
                    <strong>Image:</strong> [Image preview]
                </div>
            </div>`;
            }

            // Clean and create a clickable item div for the description
            var cleanDescription = stripHtml(item.description);
            var limitedDescription = limitText(cleanDescription, 60);
            content += `
            <div class="collection-item" data-content="${encodeURIComponent(cleanDescription)}" data-type="description">
                <div class="item-preview">
                    <strong>Description:</strong> ${limitedDescription}
                 </div>
            </div>`;

            // Add clickable items for each paragraph
            if (item.paragraphs && Array.isArray(item.paragraphs)) {
                                    item.paragraphs.forEach(function(paragraph, index) {
                var cleanParagraph = stripHtml(paragraph);
                var limitedParagraph = limitText(cleanParagraph, 60);
                content += `
            <div class="collection-item" data-content="${encodeURIComponent(cleanParagraph)}" data-type="paragraph">
                <div class="item-preview">
                    <strong>Paragraph ${index + 1}:</strong> ${limitedParagraph}
                </div>
            </div>`;
              });
            }
          });


          // Insert the generated content into the modal
          document.getElementById("modalContent").innerHTML = content;

          // Add styling for the collection items
                            var style = document.createElement('style');
          style.innerHTML = `
        .collection-item {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .collection-item:hover {
            background-color: #f0f8ff;
        }
        .item-preview {
            overflow: hidden;
        }
    `;

          document.head.appendChild(style);

                            $('.collection-item').on('click', function() {
            // Retrieve the encoded content and decode it
                                var content = decodeURIComponent($(this).attr('data-content'));
                                var contentType = $(this).attr('data-type');

            // Limit the content to 50 words before inserting
            var limitedContent = limitText(content, 60);

            // Get the target element where the content should be inserted
                                var $targetElement = $('#myModal').data('currentElement');

            // Insert the limited text content
            $targetElement.text(limitedContent);

            // Show feedback
            showFeedback($targetElement, contentType + " inserted!");

            // Close the modal
            closeModal();
          });

        }
      }
    };
    xhr.send();
  }

  // Update the modal content to remove the Insert Selected button
            $(document).ready(function() {
    // Update modal HTML
                $('body').find('#myModal').html(`
    <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <span class="close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h2>My Collection</h2>
        <p>Click an item to insert it:</p>
        <div id="modalContent" style="margin-top: 15px; max-height: 400px; overflow-y: auto;">
            Loading...
        </div>
    </div>
`);
  });

  // Close the modal when the <span> (close button) is clicked
            $(document).on('click', '.close', closeModal);

  // Close the modal if the user clicks outside the modal content
            $(window).on('click', function(event) {
    var modal = document.getElementById("myModal");
    if (event.target === modal) {
      closeModal();
    }
  });
});

// Add Font Awesome if not already included
        if ($('link[href*="font-awesome"]').length === 0 && $('script[src*="font-awesome"]').length === 0) {
            $('head').append('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">');
}

function setupImageUploads() {
            $('#canvasArea img').each(function() {
      const img = $(this);
                const uploadBtn = $('<input type="file" accept="image/*" style="display: none;">').insertAfter(img);

                const changeBtn = $('<button class="image-upload-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97L9 12v3h3zM16 5l3 3"/></g></svg></button>').insertAfter(img).css({
                    'position': 'absolute',
                    'background': '#007bff',
                    'color': 'white',
                    'border': 'none',
                    'padding': '5px 10px',
                    'cursor': 'pointer',
                    'z-index': '400'
          // 'display': 'none'
      });


                changeBtn.click(function() {
          uploadBtn.click();
      });

                uploadBtn.change(function(e) {
          const file = e.target.files[0];
          if (file) {
              const formData = new FormData();
                        formData.append('image', file);

              $.ajax({
                            url: 'upload.php', // PHP script to handle file upload
                            type: 'POST',
                  data: formData,
                  contentType: false,
                  processData: false,
                            success: function(response) {
                      if (response.startsWith("uploads/NW_images/")) {
                          let tempSrc = response; // Temporary src while uploading
                                    img.attr('src', tempSrc);

                          // After saving, update src by removing "uploads/"
                          // let finalSrc = response.replace("uploads/", "");
                          // setTimeout(function() {
                          //     img.attr('src', finalSrc);
                          // },2000); 
                      } else {
                                    alert('Error: ' + response);
                      }
                  },
                            error: function() {
                                alert('Image upload failed.');
                            }
              });

          }
      });
  });
}

function addSaveButton(templatePath) {
  // Add save button at the top
            const saveBtn = $('<button class="image-upload-btn"  id="saveTemplate">Save Changes</button>')
                .prependTo('#canvasArea')
    .css({
                    'position': 'fixed',
                    'top': '100px',
                    'right': '20px',
                    'cursor': 'pointer',
                    'z-index': '400'
    });

            saveBtn.click(function() {
    saveTemplate(templatePath);
  });
}
// Frontend JavaScript
function saveTemplate() {
  // Prompt for template name
  const templateName = prompt("Please enter a name for your template:");
  const templateEmail = prompt("Please enter notification reciver email:");

  // If user cancels the prompt or enters empty string, abort save
  if (!templateName) {
    return;
  }

  // Create the template path using the provided name
  // Assuming templates are stored in a 'templates' directory and using .html extension
  const templatePath = `templates/${templateName}.html`;

  // Create FormData to handle both text content and images
  const formData = new FormData();

  // Get the current state of the template
            const template = $('#canvasArea').clone();

  // Remove editing-related elements and classes
  template.find('.field-selection, input[type="file"]').remove();
  template.find('.image-upload-btn, input[type="file"]').remove();
            template.find('[contenteditable]').removeAttr('contenteditable');
            template.find('.editable-element').removeClass('editable-element');

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
      value instanceof File ? `${key}: File(${value.name})` : `${key}: ${value}`
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
        window.location.href = response.templatePath;
      } else {
        alert("Error saving template: " + (response.error || "Unknown error"));
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



    
        $(document).ready(function() {
  // Get the template parameter from URL
  const urlParams = new URLSearchParams(window.location.search);
            const templateParam = urlParams.get('template');

  // If template parameter exists, load the template
  if (templateParam) {
    loadTemplate(templateParam);
  }
});

function loadTemplate(templateParam) {
  // Show loading indicator
  $("#canvasArea").html("Loading template...");

  // Perform AJAX request
  $.ajax({
    url: "edit_template.php",
    type: "GET",
    data: {
                    template: templateParam
    },
                success: function(response) {
      // Insert the response into the canvas area
      $("#canvasArea").html(response);
      // Make all text elements editable
      makeElementsEditable();

      // Set up image uploads
      setupImageUploads();

      // Add save button functionality
      addSaveButton(templatePath);

    },

                error: function() {
      // Show error message if request fails
      $("#canvasArea").html("Error loading template.");
                }
  });
}
