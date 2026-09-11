// Function to remove <br> with new line
function replaceBrWithNewline(input) {
    // Replace <br> tags with newline characters (\n)
    var replacedString = input.replace(/<br\s*\/?>/ig, '\n');
    return replacedString;
}

// Function to copy the content
function copyText() {
    // Get the text from the div with class 'generated-text'
    var text = $('.generated-text').text().trim();

    // Create a temporary textarea element using jQuery
    var $textarea = $('<textarea>');
    $textarea.val(text);
    $('body').append($textarea);

    // Select the text in the textarea
    $textarea.select();

    try {
        // Copy the text
        var successful = document.execCommand('copy');
        if (successful) {
            alert('Text copied to clipboard');
        } else {
            alert('Failed to copy text');
        }
    } catch (err) {
        alert('Oops, unable to copy');
    }

    // Remove the temporary textarea
    $textarea.remove();
}

// Function to save the content in the database
function genai_save(generatedText, workingHeadline) {
    $.ajax({
        type: 'POST',
        url: '/genai/genai_save.php',
        data: {
            generated_content: generatedText,
            working_headline: workingHeadline
        },
        success: function (response) {
            alert(response);
        },
        error: function (xhr, status, error) {
            // Handle error
            console.error('Error saving data:', error);
            // Optionally, you can show an error message to the user
        }
    });
}

// Function to share the data on whatsapp
function share_whatsapp() {
    // Function to detect if WhatsApp is installed based on user agent
    const isWhatsAppInstalled = /WhatsApp/.test(navigator.userAgent);

    // Base URLs
    var app_url = "whatsapp://send?text=";
    var web_url = "https://web.whatsapp.com/send?text=";

    // Example values (replace with actual session values)
    var headline = $('#working_headline').val().trim();
    var content = $('.generated-text').html().trim();
    content = content.replace(/<br\s*\/?>/gi, '\n');

    // Encode headline and content
    var encoded_headline = encodeURIComponent(headline);
    var encoded_content = encodeURIComponent(content);

    // Construct the WhatsApp share URL for app
    var app_share_url = app_url + encoded_headline + encodeURIComponent("\n\n") + encoded_content;
    app_share_url = app_share_url + " Powered by Knobly Cream"; // Add your custom message here

    // Log the generated WhatsApp app share link to console (optional)
    console.log("App URL: " + app_share_url);

    // Attempt to open the WhatsApp app link in a new tab or window
    var appWindow = window.open(app_share_url, "_blank");

    // Check if the appWindow is null or closed after a timeout
    setTimeout(function() {
        if (!appWindow || appWindow.closed || typeof appWindow.closed == 'undefined') {
            // App link failed, redirect to WhatsApp Web
            console.error("Opening WhatsApp app failed, redirecting to WhatsApp Web.");
            window.location.href = web_url + encoded_headline + encodeURIComponent("\n\n") + encoded_content;
        }
    }, 2000); // Adjust the timeout as needed (2 seconds in this example)
}

function article_save(title, description) {
    console.log('Title:', title);
    console.log('Description:', description);
    
    title = convert_text(title);
    description = convert_text(description);
    
    $.ajax({
        type: 'POST',
        url: '/genai/genai_article_save.php',
        data: {
            title: title,
            description: description
        },
        success: function (response) {
            console.log('Success Response:', response);
            alert(response);
        },
        error: function (xhr, status, error) {
            console.error('Error saving data:', error);
            console.error('XHR:', xhr);
            console.error('Status:', status);
        }
    });
}

// Function to generate the content in OpenAI
// function genai_generate() {
//     var headline = $('#working_headline').val(); // Get the value of the textarea
//     console.log(avatar); // Optional: Log the avatar value
//     console.log(headline); // Optional: Log the headline value

//     // AJAX call using jQuery
//     $.ajax({
//         url: '/genai/process_genai.php', // PHP file to handle the request
//         type: 'POST', // Method type
//         data: {
//             avatar: avatar,
//             working_headline: headline
//         }, // Data to send
//         success: function (response) {
//             console.log(response);
//             // On success, update the content of the div
//             try {
//                 var decodedData = decodeURIComponent(response);
//             }
//             catch {
//                 decodedData = response;
//             }
//             decodedData = convert_text(decodedData);

//             $(".generated-content").show();
//             $('.generated-text').append(decodedData).html();
//             $('.generated-text').append("<br><br>");
//             // $('.loadingIndicator').hide();
//         },
//         error: function () {
//             // On error, show an alert or handle the error gracefully
//             alert('Error: Unable to fetch data.');
//         }
//     });
// }

function genai_generate(avatar, headline) {
    // AJAX call using jQuery
    $.ajax({
        url: '/genai/process_genai.php', // PHP file to handle the request
        type: 'POST', // Method type
        data: {
            avatar: avatar, // Assuming avatar is defined elsewhere
            working_headline: headline
        }, // Data to send
        beforeSend: function () {
            // This function executes before the AJAX request is sent
            console.log('AJAX request sending...');
        },
        success: function (response) {
            console.log('AJAX request successful');
            console.log(response);

            // On success, update the content of the div
            try {
                var decodedData = decodeURIComponent(response);
            } catch {
                decodedData = response;
            }
            decodedData = convert_text(decodedData);

            $(".generated-content").show();
            $('.generated-text').append(decodedData).html();
            $('.generated-text').append("<br><br>");

            // Hide loading indicator after successful response
            $('.loadingIndicator').hide();
        },
        error: function () {
            console.error('AJAX request failed');
            // On error, show an alert or handle the error gracefully
            alert('Error: Unable to fetch data.');
            // Hide loading indicator on error
            $('.loadingIndicator').hide();
        }
    });
}

// Function to set active button
function setActiveButton(buttonId) {
    // Remove 'active' class from all buttons
    $('.reportButton').removeClass('active-tab');

    // Add 'active' class to the clicked button
    $('#' + buttonId).addClass('active-tab');
}


// Function to replace all the markdown syntax
function convert_text(data) {
    data = replacegtandlt(data);
    data = replaceHeaders(data);
    data = replaceCodeBlock(data);
    data = replaceInlineCode(data);
    data = replaceEmphasis(data);
    data = replaceStrike(data);
    data = replaceHorizontalRule(data);
    data = replaceTasksAndEscapes(data);
    data = replaceLists(data);
    data = replaceImages(data);
    data = replaceLinks(data);
    data = replaceBlockquotes(data);
    data = replaceTables(data);
    data = replaceSuperscriptAndSubscript(data);
    return data;
}

function replacegtandlt(text) {
    text = text.replace(/</g, '&lt;');
    text = text.replace(/>/g, '&gt;');
    return text;
}

function replaceHeaders(text) {
    let inCodeBlock = false;
    let lines = text.split('\n');
    let output = [];

    for (let line of lines) {
        if (line.trim().startsWith('```python')) {
            inCodeBlock = true;
            output.push(line);
        } else if (line.trim().startsWith('```php')) {
            inCodeBlock = true;
            output.push(line);
        } else if (line.trim().startsWith('```')) {
            inCodeBlock = false;
            output.push(line);
        } else if (inCodeBlock) {
            output.push(line);
        } else {
            // Replace Markdown headers only if not inside a code block
            line = line.replace(/(?<!`{3}.*)(#{7,}) (.*?)(\r?\n|$)/g, '<h6>$2</h6>');
            line = line.replace(/(?<!`{3}.*)(######) (.*?)(\r?\n|$)/g, '<h6>$2</h6>');
            line = line.replace(/(?<!`{3}.*)(#####) (.*?)(\r?\n|$)/g, '<h6>$2</h6>');
            line = line.replace(/(?<!`{3}.*)(####) (.*?)(\r?\n|$)/g, '<h5>$2</h5>');
            line = line.replace(/(?<!`{3}.*)(###) (.*?)(\r?\n|$)/g, '<h4>$2</h4>');
            line = line.replace(/(?<!`{3}.*)(##) (.*?)(\r?\n|$)/g, '<h3>$2</h3>');
            line = line.replace(/(?<!`{3}.*)(#) (.*?)(\r?\n|$)/g, '<h2>$2</h2>');

            output.push(line);
        }
    }

    return output.join('\n');
}

function replaceEmphasis(text) {
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
    return text;
}

function replaceLists(text) {
    // Replace Markdown unordered list with HTML <ul> tags
    text = text.replace(/^\s*-\s*(.*)$/gm, '<ul><li>$1</li></ul>');

    // // Replace Markdown ordered list with HTML <ol> tags
    // text = text.replace(/^\s*\d+\.\s*(.*)$/gm, '<ol><li>$1</li></ol>');

    // Replace Markdown ordered list with HTML <ol> tags
    text = text.replace(/^\s*\d+\.\s*(.*)$/gm, '<ul><li>$1</li></ul>');

    return text;
}

function replaceInlineCode(text) {
    return text.replace(/`(.*?)`/g, '<code>$1</code>');
}

function replaceStrike(text) {
    return text.replace(/~~(.*?)~~/g, '<del>$1</del>');
}

function replaceCodeBlock(text) {
    text = text.replace(/```([^`]*?)\n([\s\S]*?)```/g, function (match, languageIdentifier, code) {
        // Default values
        let language = languageIdentifier.trim();
        const codeContent = code.trim();

        // Check if language is empty
        let languageClass = language ? '' : 'hidden'; // Add 'hidden' class if language is empty

        return `
            <div class="code-block">
                <div class="code-language ${languageClass}">${language}</div>
                <button class="copy-button" onclick="copyToClipboard(this)">
                    <i class="far fa-copy fa-lg fa-fw"></i>
                </button>
                <pre><code>${codeContent}</code></pre>
            </div>
        `;
    });

    return text;
}

// Function to copy code to clipboard
function copyToClipboard(button) {
    const codeElement = button.parentElement.querySelector('code');
    const codeText = codeElement.textContent;

    navigator.clipboard.writeText(codeText)
        .then(() => {
            console.log('Code copied to clipboard');
            // Optionally show a message or update UI
        })
        .catch(err => {
            console.error('Failed to copy: ', err);
            // Handle error
        });
}

function replaceLinks(text) {
    var pattern = /\[([^\]]+)\]\(([^)]+)\)/g;
    var replacement = '<a href="$2">$1</a>';
    return text.replace(pattern, replacement);
}

function replaceImages(text) {
    var pattern = /!\[([^\]]+)\]\(([^)]+)\)/g;
    var replacement = '<img src="$2" alt="$1">';
    return text.replace(pattern, replacement);
}

function replaceBlockquotes(text) {
    var pattern = /^> (.+)$/gm;
    var replacement = '<blockquote>$1</blockquote>';
    return text.replace(pattern, replacement);
}

function replaceHorizontalRule(text) {
    var pattern = /^\s*---+\s*$/gm;
    var replacement = '<hr>';
    return text.replace(pattern, replacement);
}

function replaceTables(text) {
    var pattern = /^\|(.+)\|$/gm;

    // Check if the text begins and ends with a table row
    if (pattern.test(text)) {
        // Replace Markdown tables with HTML <table> tags
        text = text.replace(pattern, function (match) {
            var cells = match.split('|').map(function (cell) {
                return '<td>' + cell.trim() + '</td>';
            }).join('');
            return '<tr>' + cells + '</tr>';
        });

        // Wrap the whole table with <table> tags
        text = '<table>' + text + '</table>';
    }

    return text;
}

function replaceTasksAndEscapes(text) {
    // Parse task lists
    text = text.replace(/^- \[(x|X| )\]/gm, '<input type="checkbox" disabled checked>');
    text = text.replace(/^- \[ \]/gm, '<input type="checkbox" disabled>');

    // Parse backslash escapes
    text = text.replace(/\\/g, '');

    return text;
}

function replaceSuperscriptAndSubscript(text) {
    // Parse superscript (^) and subscript (~) syntax
    text = text.replace(/\^([^\s]+)\^/g, '<sup>$1</sup>');
    text = text.replace(/~([^\s]+)~/g, '<sub>$1</sub>');

    return text;
}

// Capitalise the title and headline
function capitalizeWords(input) {
    // Split the input string into words
    var words = input.split(' ');

    // Capitalize the first letter of each word except short words (length == 2 or length == 3)
    for (var i = 0; i < words.length; i++) {
        var word = words[i];
        // Check if the length of the word is not 2 and not 3
        if (word.length != 2 && word.length != 3) {
            words[i] = word.charAt(0).toUpperCase() + word.slice(1); // Capitalize the word
        }
    }

    // Join the words back into a string and return
    return words.join(' ');
}