<!-- New Dynamic Form Section -->
<section class="contact-form-section">
  <div class="container">
    <!-- Form Field Selection -->
    <div class="field-selection edit-control">
      <h3>Select Required Fields</h3>
      <div class="checkbox-container">
        <div class="checkbox-item">
          <input
            type="checkbox"
            id="nameField"
            name="fieldSelect"
            value="name"
          />
          <label for="nameField">Name</label>
        </div>
        <div class="checkbox-item">
          <input
            type="checkbox"
            id="instituteField"
            name="fieldSelect"
            value="institute"
          />
          <label for="instituteField">Institute/Organization</label>
        </div>
        <div class="checkbox-item">
          <input
            type="checkbox"
            id="designationField"
            name="fieldSelect"
            value="designation"
          />
          <label for="designationField">Designation</label>
        </div>
        <div class="checkbox-item">
          <input
            type="checkbox"
            id="emailField"
            name="fieldSelect"
            value="email"
          />
          <label for="emailField">Email</label>
        </div>
        <div class="checkbox-item">
          <input
            type="checkbox"
            id="phoneField"
            name="fieldSelect"
            value="phone"
          />
          <label for="phoneField">Phone Number</label>
        </div>
        <div class="checkbox-item">
          <input
            type="checkbox"
            id="queryField"
            name="fieldSelect"
            value="query"
          />
          <label for="queryField">Free Space</label>
        </div>
        <div class="checkbox-item">
          <input
            type="checkbox"
            id="stateField"
            name="fieldSelect"
            value="state"
          />
          <label for="stateField">State</label>
        </div>
        <div class="checkbox-item">
          <input
            type="checkbox"
            id="cityField"
            name="fieldSelect"
            value="city"
          />
          <label for="cityField">City</label>
        </div>
      </div>
    </div>

    <h2 class="form-title" id="form-title">Enter Form Name</h2>

    <form id="frmLead" class="contact-form" onsubmit="return chkLead()">
      <div id="formFields">
        <!-- Dynamic form fields will be inserted here -->
      </div>

      <div id="panelStatus" class="status-panel"></div>

      <div class="submit-section">
        <button type="submit" id="btnSubmit" class="submit-button">
          Submit
        </button>
        <div id="contentLoader" class="loader" style="display: none">
          Processing...
        </div>
      </div>
    </form>
  </div>
</section>




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
    const newField = formFields.querySelector(
      `[data-field="${field}"]`
    );
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
document
.querySelectorAll('input[name="fieldSelect"]')
.forEach((checkbox) => {
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