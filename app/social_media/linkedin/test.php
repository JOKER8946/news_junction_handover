<div class="profile-card">
    <div class="profile-image">??</div>
    <div class="profile-info">
        <h2>Name</h2>
        <p>CEO & Founder, Example</p>
        <p>Harvard University</p>
        <a href="#" class="linkedin-button">LinkedIn: Go to my profile</a><br>

        <button class="expires-button expires-banner">i</button>
    </div>
</div>
-------------------
<style>
    body {
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f4f4f4;
    }

    .profile-card {
        display: flex;
        align-items: center;
        background: white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        overflow: hidden;
        max-width: 600px;
        width: 100%;
    }

    .profile-image {
        width: 150px;
        height: 150px;
        background-color: #007bff;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 48px;
        border-radius: 50%;
        margin: 20px;
        flex-shrink: 0;
    }

    .profile-info {
        flex-grow: 1;
        padding: 20px;
    }

    .profile-info h2 {
        margin: 0;
        font-size: 22px;
    }

    .profile-info p {
        color: #555;
        margin: 5px 0;
    }

    .linkedin-button {
        margin: 10px 0;
        background-color: #0077b5;
        color: white;
        padding: 8px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
    }

    .expires-banner {
        background-color: black;
        color: white;
        padding: 10px;
        font-size: 14px;
        flex-shrink: 0;
        text-align: center;
        min-width: 150px;
    }
</style>