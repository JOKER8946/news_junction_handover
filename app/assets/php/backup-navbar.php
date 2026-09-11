 <style>
     .navbar-brand {
         font-weight: 700;
         font-size: 1.5rem;
         color: #e50914 !important;
         /* Netflix Red */
     }

     /* Navbar links */
     .navbar-nav .nav-link {
         color: #bbb !important;
         transition: color 0.3s;
     }

     .navbar-nav .nav-link:hover {
         color: #fff !important;
     }

     .navbar {
         background-color: #141414;
     }


     /* CSS for the navbar */
     .navbar {
         position: sticky;
         /* Makes the navbar sticky */
         top: 0;
         /* Sticks to the top */
         z-index: 1000;
         /* Ensures it stays on top of other content */
         transition: all 0.3s ease;
     }

     .navbar.scrolled {
         padding: 10px 0;
         /* Adjust the padding for a smaller navbar */
         background-color: #000;
         /* Change background color if needed */
     }

     /* 
        .navbar img {
            height: 60px;
            transition: height 0.3s ease;
        } */

     .navbar.scrolled img {
         height: 20px;
         /* Smaller logo height on scroll */
     }

     /* Mobile styles */
     @media (max-width: 768px) {
         .navbar {
             padding: 8px;
             /* Initial padding for mobile */
         }

         .navbar.scrolled {
             padding: 10px 0;
             /* Smaller padding when scrolled */
         }

     }

     .nav-item button {

         border: none !important;
         /* border-radius: 100% !important; */
         background-color: transparent;
         outline: none !important;
     }

     .nav-item button span {
         /* display: flex; */
         /* position: relative; */
         /* right: 5px; */
         align-items: center;
         justify-items: center;
         /* border-radius: 100%; */
         background-color: transparent !important;
     }

     /* button:focus {
            outline: 1px dotted;
            outline: 5px auto -webkit-focus-ring-color;
        } */

     #theme-icon img {
         fill: white;
         /* Change the color to white */
     }

     /* .dropdown-menu.show {
         display: block;
         right: 50px;
         position: absolute;
         padding: 10px;
         display: block;
     } */

     #toggle-mode {
         text-decoration: none;
         /* Prevent underline or other hover styles */
         cursor: pointer;
         /* Optional: Make it look like a clickable button */
     }

     #toggle-mode,
     .dropdown-item:hover {
         color: none;
         /* Ensure text color doesn't change on hover */
         background-color: transparent;
         /* Prevent any background color change on hover */
     }

     .dropdown-item:focus,
     .dropdown-item:hover {
         color: var(--bs-dropdown-link-hover-color);
         background-color: none !important;
     }

     body.light-mode #toggle-mode #mode-text {
         color: #141414;
         /* Ensure the text color remains consistent */
     }

     body.dark-mode #toggle-mode #mode-text {
         color: white;
         /* Ensure the text color remains consistent */
     }

     .dropdown-menu .dropdown-menu-end li:hover {
         color: none;
         background-color: none;

     }



     .avatar {
         width: 30px;
         height: 30px;
         border-radius: 50%;
         border: 0.02px solid whitesmoke;
         overflow: hidden;
     }

     .avatar img {
         width: 100%;
         height: 100%;
         object-fit: cover;
     }

     @media screen and (max-width:650px) {
         .dropdown-menu .dropdown-menu-end {
             padding: 10px;
             display: block;
             position: absolute;
             top: 42px;
             right: 35px;
         }

     }
 </style>

 <nav class="navbar navbar-expand-lg">
     <div class="container d-flex justify-content-between">
         <a class="navbar-brand" href="/stream.php">
             <img src="/assets/img/logo.black.png" height="60px" alt="Logo">
         </a>
         <div class="end-navbar">
             <div class="nav-item dropdown " style="display:flex; gap:4px; justify-items:center">
                 <div class="avatar me-3">
                     <img src="<?= viewProfilePic($db, $gUserId) ?>" alt="Default Image" onerror="this.onerror=null; this.src='/data/profilePic/default.png';">
                 </div>

                 <!-- <button id="toggle-mode" class="toggle-button">
                     <span id="theme-icon">&#9728;</span>
                 </button> -->

                 <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                     <span id="settings-text">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                             <path fill="currentColor" d="m14.136 3.361l.995-.1zm-.152-.82L13.095 3zm.447 2.277l.795-.607zm.929.384l-.134-.99zm1.238-.82l.633.773zm.687-.473l.305.953zm.702.035l.398-.917zm.637.538l-.707.707zm.894.894l.707-.707zm.538.637l.917-.398zm.035.702l.952.304zm-.472.687l-.774-.633zm-.822 1.239l-.99-.134zm.385.928l-.606.795zm1.457.295l.099-.995zm.82.152l.458-.889zm.47.521l.93-.367zm.001 2.926l-.93-.368zm-.472.52l.459.89zm-.82.153l-.099-.995l-.033.003l-.032.006zm0 0l.1.995l.033-.003l.032-.005zm-1.456.295l-.606-.795zm-.384.929l-.991.133zm.821 1.238l-.774.633zm.472.687l-.953.304zm-.035.702l-.918-.398zm-.538.637l.707.707zm-.894.893l-.707-.707zm-.637.538l.398.918zm-.702.035l-.304.953zm-.687-.472l.633-.774l-.008-.006zm0 0l-.633.774l.008.007zm-1.238-.82l.133-.992zm-.929.384l.795.606zm-.295 1.456l-.995-.1zm-.152.82L13.095 21zm-.521.472l-.368-.93zm-2.926 0l.368-.93zm-.52-.472l.888-.458zm-.153-.82l-.995.1zm-.295-1.456l-.795.607zm-.928-.384l-.134-.992zm-1.239.82l-.633-.773l-.016.013l-.015.013zm0 0l.633.775l.016-.013l.015-.014zm-.687.473l.304.952zm-.702-.035l-.398.917zm-.637-.538l.707-.707zm-.894-.894l-.707.707zm-.538-.637l.918-.397zm-.035-.702l.953.305zm.472-.687l.774.633zm.821-1.239l.992.134zm-.384-.928l.606-.795zm-1.457-.295l-.1.995zm-.82-.152L3 13.095zm-.47-.521l-.93.367zm0-2.926l-.93-.368zm.47-.52l-.458-.89zm.82-.153v-1h-.05l-.049.005zm0 0v1h.05l.05-.005zm1.457-.295l-.606-.795zm.385-.928l.991-.134zM4.38 7.4l.774-.632zm-.472-.687l.953-.304zm.035-.702l-.917-.397zm.538-.637l.707.707zm.894-.893l-.707-.707zm.637-.538l-.398-.918zm.702-.035l.304-.953zm.687.472l.633-.774zm1.238.821l.134-.991zm.93-.385l-.796-.606zm.294-1.456l.995.1zm.152-.82l-.889-.458zm.521-.471l.368.93zm2.926 0l.367-.93zm1.668 1.192a9 9 0 0 0-.068-.575a2 2 0 0 0-.19-.604L13.095 3c-.023-.045-.018-.061-.005.018c.015.089.028.213.051.443zm.095.95c.063.082.043.119.008-.076c-.033-.186-.06-.447-.103-.874l-1.99.199c.04.394.074.748.125 1.03c.049.27.135.625.37.933zm0 0l-1.59 1.212a2 2 0 0 0 1.857.77zm.739-.605a13 13 0 0 1-.691.545c-.163.113-.151.073-.048.06l.267 1.982c.384-.052.696-.242.922-.4c.235-.162.51-.388.816-.639zm1.016-.65a2 2 0 0 0-.561.292c-.14.1-.297.229-.455.358l1.266 1.548c.179-.146.276-.225.35-.277c.065-.047.056-.031.009-.016zm1.404.07a2 2 0 0 0-1.404-.07l.609 1.905zm.946.748a9 9 0 0 0-.417-.402a2 2 0 0 0-.53-.346l-.794 1.835c-.046-.02-.053-.036.007.017c.068.06.157.147.32.31zm.894.894l-.894-.894l-1.414 1.414l.894.894zm.748.946a2 2 0 0 0-.346-.53a9 9 0 0 0-.402-.416L18.81 6.083c.163.163.25.252.31.32c.053.06.037.053.017.007zm.07 1.404a2 2 0 0 0-.07-1.404l-1.835.795zm-.65 1.016a9 9 0 0 0 .358-.455c.106-.148.22-.332.292-.561l-1.905-.609c.015-.047.03-.056-.016.01c-.052.073-.13.17-.277.349zm-.605.739c-.013.103-.053.115.06-.048c.107-.155.273-.358.545-.69l-1.548-1.267c-.25.306-.477.581-.64.816c-.157.226-.347.538-.399.922zm0 0l-1.982-.267a2 2 0 0 0 .77 1.857zm.95.095a13 13 0 0 1-.874-.103c-.195-.035-.158-.055-.076.008l-1.212 1.59c.308.235.662.321.934.37c.281.05.635.085 1.029.125zm1.179.258a2 2 0 0 0-.604-.19a9 9 0 0 0-.575-.068l-.199 1.99a9 9 0 0 1 .443.05c.08.014.063.019.018-.004zm.943 1.043a2 2 0 0 0-.943-1.043L21 10.906zm.14 1.198c0-.204 0-.407-.011-.579a2 2 0 0 0-.13-.62L21 10.906c-.018-.047-.012-.063-.006.017c.006.09.006.215.006.446zm0 1.264v-1.264h-2v1.264zm-.14 1.198c.088-.223.117-.437.129-.62c.011-.171.011-.374.011-.578h-2c0 .231 0 .356-.006.446c-.006.08-.012.064.006.017zm-.943 1.043a2 2 0 0 0 .943-1.043L21 13.095zm-1.179.258c.204-.02.405-.04.575-.068c.18-.03.39-.08.604-.19L21 13.095c.044-.023.061-.018-.018-.005a8 8 0 0 1-.443.051zm.065-.008l-.329-1.973zm-1.014.103c-.083.063-.12.043.075.008c.186-.033.447-.06.874-.103l-.199-1.99c-.393.04-.748.074-1.029.125c-.271.049-.626.135-.934.37zm0 0l-1.213-1.59a2 2 0 0 0-.77 1.857zm.604.738a13 13 0 0 1-.545-.69c-.113-.163-.073-.151-.06-.048l-1.981.267c.052.384.241.696.399.922c.163.235.389.51.639.816zm.65 1.016a2 2 0 0 0-.292-.56c-.1-.141-.229-.297-.358-.456l-1.548 1.267c.146.179.225.275.277.349c.047.065.032.057.016.01zm-.07 1.405a2 2 0 0 0 .07-1.405l-1.905.61zm-.748.946c.145-.145.288-.287.402-.417c.12-.138.25-.309.346-.53l-1.835-.795c.02-.046.036-.052-.017.008c-.06.068-.147.156-.31.32zm-.894.893l.894-.893l-1.414-1.414l-.894.893zm-.946.749a2 2 0 0 0 .53-.347c.129-.113.272-.257.416-.402l-1.414-1.414a8 8 0 0 1-.32.31c-.06.054-.053.038-.007.018zm-1.404.07a2 2 0 0 0 1.404-.07l-.795-1.835zm-1.016-.65c.158.129.314.258.455.358c.148.105.332.219.561.292l.609-1.905c.047.015.056.03-.01-.016a8 8 0 0 1-.349-.277zm.008.006l1.25-1.561zm-.747-.61c-.103-.015-.115-.055.048.058c.155.108.358.273.69.545l1.267-1.547c-.306-.251-.581-.477-.816-.64c-.226-.158-.538-.347-.922-.399zm0 0l.267-1.983a2 2 0 0 0-1.857.77zm-.095.949c.043-.427.07-.689.103-.874c.035-.195.055-.159-.008-.076l-1.59-1.213c-.235.308-.321.663-.37.934c-.05.282-.085.636-.125 1.03zm-.259 1.179c.11-.214.16-.424.19-.604c.03-.17.049-.371.07-.575l-1.99-.2a8 8 0 0 1-.052.444c-.013.08-.018.063.005.018zm-1.041.943a2 2 0 0 0 1.041-.943L13.095 21zm-1.2.14c.205 0 .408 0 .58-.011c.182-.012.396-.04.62-.13L13.095 21c.047-.018.063-.012-.017-.006a8 8 0 0 1-.446.006zm-1.263 0h1.264v-2h-1.264zm-1.198-.14c.223.088.437.117.62.129c.171.011.374.011.578.011v-2c-.231 0-.356 0-.446-.006c-.08-.006-.064-.012-.017.006zm-1.043-.943a2 2 0 0 0 1.043.943l.735-1.86zm-.258-1.179c.02.204.04.405.068.575c.03.18.08.39.19.604l1.78-.916c.023.044.018.061.005-.018a8 8 0 0 1-.051-.443zm-.095-.95c-.063-.082-.043-.12-.008.076c.033.185.06.447.103.874l1.99-.199c-.04-.394-.074-.748-.125-1.03c-.049-.27-.135-.625-.37-.933zm0 0l1.59-1.212a2 2 0 0 0-1.857-.77zm-.739.605c.333-.272.536-.438.691-.545c.163-.113.151-.073.048-.06l-.267-1.982c-.384.052-.696.242-.922.4c-.235.162-.51.388-.816.639zm.031-.027L6.737 18.87zm-1.047.677a2 2 0 0 0 .561-.292c.14-.1.297-.229.455-.358L6.77 18.845a8 8 0 0 1-.35.277c-.065.047-.056.032-.009.016zm-1.404-.07a2 2 0 0 0 1.404.07l-.609-1.905zm-.947-.748c.145.145.288.288.418.402c.137.12.308.25.53.346l.794-1.835c.046.02.053.036-.007-.017a8 8 0 0 1-.32-.31zm-.893-.894l.894.894l1.414-1.414l-.894-.894zm-.748-.946c.095.22.226.392.346.53c.114.129.257.272.402.416l1.414-1.414a8 8 0 0 1-.31-.32c-.053-.06-.037-.053-.017-.007zm-.07-1.404a2 2 0 0 0 .07 1.404l1.835-.795zm.65-1.016a9 9 0 0 0-.358.455a2 2 0 0 0-.292.561l1.905.609c-.016.047-.03.056.016-.01c.052-.073.13-.17.277-.349zm.604-.739c.014-.103.054-.115-.059.048c-.107.155-.273.358-.545.69l1.548 1.267c.25-.306.477-.581.64-.816c.157-.226.347-.538.399-.922zm0 0l1.983.267a2 2 0 0 0-.77-1.857zm-.95-.095c.428.043.69.07.875.103c.195.035.158.055.075-.008l1.213-1.59c-.308-.235-.662-.321-.934-.37c-.281-.05-.635-.085-1.03-.125zm-1.178-.258c.214.11.424.16.604.19c.17.028.371.048.575.068l.199-1.99a8 8 0 0 1-.443-.05c-.08-.014-.063-.019-.018.004zM1.14 13.83a2 2 0 0 0 .943 1.043L3 13.095zM1 12.632c0 .204 0 .407.011.579c.012.182.04.396.13.62L3 13.094c.018.047.012.063.007-.017A8 8 0 0 1 3 12.632zm0-1.264v1.264h2v-1.264zm.14-1.199a2 2 0 0 0-.129.62c-.012.172-.011.375-.011.58h2c0-.232 0-.357.007-.447c.005-.08.011-.064-.007-.017zm.943-1.041a2 2 0 0 0-.943 1.041l1.86.736zm1.179-.26c-.204.021-.405.04-.575.07c-.18.03-.39.08-.604.19L3 10.905c-.045.023-.061.018.018.005a8 8 0 0 1 .443-.051zm.1-.004v2zm0 0v2zm.85-.09c.083-.063.12-.043-.076-.008c-.185.033-.447.06-.874.103l.2 1.99c.393-.04.747-.074 1.029-.125c.271-.049.626-.135.934-.37zm0 0l1.213 1.59a2 2 0 0 0 .769-1.857zm-.605-.739c.272.332.438.536.546.691c.113.163.073.151.059.048l1.982-.267c-.052-.384-.241-.695-.399-.922c-.163-.235-.39-.51-.64-.816zm-.65-1.017c.073.23.186.413.292.562c.1.14.229.297.358.455L5.155 6.77a8 8 0 0 1-.277-.35c-.047-.065-.031-.057-.016-.01zm.07-1.403a2 2 0 0 0-.07 1.403l1.905-.608zm.748-.947c-.145.145-.288.287-.402.417a2 2 0 0 0-.346.53l1.835.795c-.02.046-.036.053.017-.008c.06-.068.147-.156.31-.32zm.894-.893l-.894.893L5.19 6.082l.894-.893zm0 0l1.414 1.414zm.946-.749a2 2 0 0 0-.53.347a9 9 0 0 0-.416.402l1.414 1.414c.163-.164.252-.251.32-.31c.06-.054.053-.038.007-.018zm1.404-.07a2 2 0 0 0-1.404.07l.795 1.835zm1.016.65c-.158-.129-.314-.257-.455-.357a2 2 0 0 0-.561-.293L6.41 4.861c-.047-.015-.056-.03.01.016c.073.053.17.131.349.278zm.739.605c.103.014.115.054-.048-.059a13 13 0 0 1-.69-.545L6.768 5.155c.306.25.581.476.816.64c.226.157.538.346.922.398zm0 0l-.267 1.982a2 2 0 0 0 1.857-.77zm.095-.95c-.043.428-.07.69-.103.875c-.035.195-.055.158.008.075l1.59 1.213c.235-.308.321-.663.37-.934c.05-.281.086-.636.125-1.03zm.258-1.178a2 2 0 0 0-.19.604c-.028.17-.048.371-.068.575l1.99.199a9 9 0 0 1 .05-.443c.014-.08.019-.062-.004-.018zm1.043-.943a2 2 0 0 0-1.043.943L10.905 3zM11.368 1c-.204 0-.407 0-.579.011a2 2 0 0 0-.62.129L10.906 3c-.047.018-.063.012.017.007c.09-.006.215-.007.446-.007zm1.264 0h-1.264v2h1.264zm1.198.14a2 2 0 0 0-.62-.129C13.04.999 12.837 1 12.633 1v2c.231 0 .356 0 .446.007c.08.005.064.011.017-.007zm1.043.943a2 2 0 0 0-1.043-.943L13.095 3zM15 12a3 3 0 0 1-3 3v2a5 5 0 0 0 5-5zm-3-3a3 3 0 0 1 3 3h2a5 5 0 0 0-5-5zm-3 3a3 3 0 0 1 3-3V7a5 5 0 0 0-5 5zm3 3a3 3 0 0 1-3-3H7a5 5 0 0 0 5 5z" />
                         </svg>
                     </span>
                 </a>

                 <ul class="dropdown-menu dropdown-menu-end flex flex-col justify-center " style="padding: 10px;" aria-labelledby="settingsDropdown">
                     <li>
                         <a class="dropdown-item" href="/my_settings.php">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                 <path fill="currentColor" d="M12 4a4 4 0 0 1 4 4a4 4 0 0 1-4 4a4 4 0 0 1-4-4a4 4 0 0 1 4-4m0 2a2 2 0 0 0-2 2a2 2 0 0 0 2 2a2 2 0 0 0 2-2a2 2 0 0 0-2-2m0 7c2.67 0 8 1.33 8 4v3H4v-3c0-2.67 5.33-4 8-4m0 1.9c-2.97 0-6.1 1.46-6.1 2.1v1.1h12.2V17c0-.64-3.13-2.1-6.1-2.1" />
                             </svg>
                             <?php echo htmlspecialchars($gUserName); ?>
                             <!-- <h2 style="font-size:medium; padding:0px 15px; color:#fff"><?php echo htmlspecialchars($gUserName); ?></h2> -->
                         </a>
                     </li>
                     <!-- <li><a class="dropdown-item" href="">Account</a></li> -->
                     <!-- <li><a class="dropdown-item" href="kannada/dashboard.php">ಕನ್ನಡ</a></li> -->
                     <li>
                         <a class="dropdown-item" href="/my_collection.php">
                             <i class="fas fa-newspaper"></i> My Collection
                         </a>
                     </li>
                     <li><a class="dropdown-item" href="/stream.php">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                 <path fill="currentColor" fill-rule="evenodd" d="M5.467 4.392a.75.75 0 0 1-.001 1.06A9.22 9.22 0 0 0 2.75 12a9.22 9.22 0 0 0 2.775 6.606a.75.75 0 1 1-1.05 1.071A10.72 10.72 0 0 1 1.25 12c0-2.972 1.207-5.664 3.156-7.609a.75.75 0 0 1 1.06.001m13.15.072a.75.75 0 0 1 1.06.011A10.72 10.72 0 0 1 22.75 12c0 2.964-1.2 5.65-3.141 7.594a.75.75 0 1 1-1.062-1.06A9.22 9.22 0 0 0 21.25 12a9.22 9.22 0 0 0-2.644-6.475a.75.75 0 0 1 .01-1.06M8.308 7.488a.75.75 0 0 1-.035 1.06c-.949.888-1.524 2.102-1.524 3.434c0 1.348.589 2.575 1.558 3.466a.75.75 0 1 1-1.016 1.104c-1.252-1.151-2.042-2.77-2.042-4.57c0-1.779.771-3.38 2-4.53a.75.75 0 0 1 1.06.036m7.434.038a.75.75 0 0 1 1.06-.024c1.197 1.145 1.947 2.727 1.947 4.48c0 1.775-.767 3.373-1.99 4.521a.75.75 0 1 1-1.027-1.093c.945-.887 1.517-2.1 1.517-3.428c0-1.313-.559-2.512-1.484-3.396a.75.75 0 0 1-.023-1.06m-3.15 1.362l.052.03a13 13 0 0 1 .694.404c.245.155.505.337.761.525l.046.033c.408.3.79.58 1.06.864c.314.328.544.727.544 1.256c0 .53-.23.928-.543 1.257c-.27.283-.653.563-1.061.863a18 18 0 0 1-.807.558c-.215.136-.453.273-.694.405l-.053.029c-.4.22-.79.432-1.132.543c-.409.132-.882.161-1.336-.146c-.428-.289-.604-.717-.692-1.125c-.08-.373-.11-.845-.143-1.367l-.004-.052c-.021-.33-.035-.662-.035-.965s.014-.634.035-.965l.004-.052c.033-.522.063-.994.143-1.367c.088-.408.264-.836.692-1.125c.454-.307.927-.278 1.336-.146c.342.11.732.324 1.132.543m-1.642.87a1 1 0 0 0-.052.174c-.054.25-.079.608-.117 1.2c-.02.31-.032.608-.032.868s.012.558.032.869c.038.59.063.95.117 1.199a1 1 0 0 0 .052.174l.048-.014c.19-.062.451-.201.926-.46a12 12 0 0 0 .613-.358c.205-.13.436-.29.675-.466c.47-.345.74-.547.908-.723c.13-.135.13-.184.129-.217v-.008c0-.033 0-.082-.129-.217c-.167-.175-.438-.378-.909-.723a12 12 0 0 0-.674-.466c-.18-.114-.39-.235-.613-.357c-.475-.26-.736-.4-.926-.46z" clip-rule="evenodd" />
                             </svg> Stream</a></li>
                     <li><a class="dropdown-item" href="/dashboard.php">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                 <path fill="currentColor" d="M14 9.9V8.2q.825-.35 1.688-.525T17.5 7.5q.65 0 1.275.1T20 7.85v1.6q-.6-.225-1.213-.337T17.5 9q-.95 0-1.825.238T14 9.9m0 5.5v-1.7q.825-.35 1.688-.525T17.5 13q.65 0 1.275.1t1.225.25v1.6q-.6-.225-1.213-.338T17.5 14.5q-.95 0-1.825.225T14 15.4m0-2.75v-1.7q.825-.35 1.688-.525t1.812-.175q.65 0 1.275.1T20 10.6v1.6q-.6-.225-1.213-.338T17.5 11.75q-.95 0-1.825.238T14 12.65M6.5 16q1.175 0 2.288.263T11 17.05V7.2q-1.025-.6-2.175-.9T6.5 6q-.9 0-1.788.175T3 6.7v9.9q.875-.3 1.738-.45T6.5 16m6.5 1.05q1.1-.525 2.213-.787T17.5 16q.9 0 1.763.15T21 16.6V6.7q-.825-.35-1.713-.525T17.5 6q-1.175 0-2.325.3T13 7.2zM12 20q-1.2-.95-2.6-1.475T6.5 18q-1.05 0-2.062.275T2.5 19.05q-.525.275-1.012-.025T1 18.15V6.1q0-.275.138-.525T1.55 5.2q1.15-.6 2.4-.9T6.5 4q1.45 0 2.838.375T12 5.5q1.275-.75 2.663-1.125T17.5 4q1.3 0 2.55.3t2.4.9q.275.125.413.375T23 6.1v12.05q0 .575-.487.875t-1.013.025q-.925-.5-1.937-.775T17.5 18q-1.5 0-2.9.525T12 20m-5-8.35" />
                             </svg> Reader</a></li>
                     <!-- <li><a class="dropdown-item" href="cream_dashboard.php">Creator</a></li> -->
                     <li> <a class="dropdown-item" href="/cream_dashboard.php">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 48 48">
                                 <g fill="currentColor">
                                     <path d="M12.5 6c-1.112 4.017-2.543 5.39-6.5 6.5c3.957 1.11 5.388 2.483 6.5 6.5c1.112-4.017 2.543-5.39 6.5-6.5c-3.957-1.11-5.388-2.483-6.5-6.5m0 17c-1.112 4.017-2.543 5.39-6.5 6.5c3.957 1.11 5.388 2.483 6.5 6.5c1.112-4.017 2.543-5.39 6.5-6.5c-3.957-1.11-5.388-2.483-6.5-6.5M23 12.5c3.957-1.11 5.388-2.483 6.5-6.5c1.112 4.017 2.543 5.39 6.5 6.5c-3.957 1.11-5.388 2.483-6.5 6.5c-1.112-4.017-2.543-5.39-6.5-6.5" />
                                     <path fill-rule="evenodd" d="m35.8 41.456l-.23-.23l-.014-.013l-18.142-18.142a2 2 0 0 1 0-2.828l2.829-2.829a2 2 0 0 1 2.828 0L41.456 35.8a2 2 0 0 1 0 2.828l-2.828 2.829a2 2 0 0 1-2.829 0M22.615 25.444l-3.787-3.787l2.828-2.829l3.788 3.788z" clip-rule="evenodd" />
                                 </g>
                             </svg> Creator</a></li>

                     <!-- <li><a class="dropdown-item" href="./request_article.php">Request Articles</a></li> -->
                     <!-- <li><a class="nav-link" href="javascript:np()" onclick="goSection('request.article', this)">
                                <div class="sb-nav-link-icon"><i class="far fa-map"></i></div> Request Article
                            </a></li> -->
                     <!-- <li><a class="dropdown-item" href="./groupchat/index.php">Groups</a></li> -->

                     <!-- <li><a class="dropdown-item" href="./analytics.php">Analytics</a></li> -->
                     <button id="toggle-mode" class="toggle-button dropdown-item">
                         <span id="theme-icon">
                             <!-- Icon will be inserted here dynamically via JavaScript -->
                         </span>
                         <span id="mode-text">Dark </span> <!-- Text that will change based on mode -->
                     </button>

                     <li>
                         <a class="dropdown-item" href="/logout.php">
                             <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                                 <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6.5C4.159 8.148 3 10.334 3 13a9 9 0 1 0 18 0c0-2.666-1.159-4.852-3-6.5M12 2v9m0-9c-.7 0-2.008 1.994-2.5 2.5M12 2c.7 0 2.008 1.994 2.5 2.5" color="currentColor" />
                             </svg> Logout
                         </a>
                     </li>
                 </ul>
             </div>
         </div>
     </div>
 </nav>


 <!-- Bootstrap JS and dependencies -->
 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
 <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>




 <!-- Custom script for dropdown and screen resizing -->
 <script>
     $(document).ready(function() {
         // Check for mobile view (screen width <= 768px)
         var isMobile = $(window).width() <= 768;

         // Ensure the dropdown opens and closes properly
         $('.dropdown-toggle').on('click', function(e) {
             e.preventDefault();
             var $dropdownMenu = $(this).next('.dropdown-menu');

             if (isMobile) {
                 // Mobile view: slide down/up the dropdown
                 if ($dropdownMenu.is(':visible')) {
                     $dropdownMenu.slideUp(); // Close if it's already open
                 } else {
                     $dropdownMenu.stop(true, true).slideDown(); // Open with slideDown
                 }

                 // Close other dropdowns if any are open
                 $('.dropdown-menu').not($dropdownMenu).slideUp();
             } else {
                 // Desktop view: Toggle without sliding
                 $dropdownMenu.toggle();

                 // Close other dropdowns if any are open
                 $('.dropdown-menu').not($dropdownMenu).hide();
             }
         });

         // Close dropdown when clicking outside of it
         $(document).on('click', function(e) {
             if (!$(e.target).closest('.nav-item.dropdown').length) {
                 if (isMobile) {
                     $('.dropdown-menu').slideUp(); // Close all dropdowns with slideUp on mobile
                 } else {
                     $('.dropdown-menu').hide(); // Just hide on desktop
                 }
             }
         });

         // Recheck if the screen size changes (in case of resizing)
         $(window).resize(function() {
             isMobile = $(window).width() <= 768;
         });
     });
 </script>

 <!-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.querySelector('.navbar');
            const logo = navbar.querySelector('img');
            const originalLogoSrc = logo.src; // Store the original logo URL
            const smallerLogoSrc = 'assets/img/logo.ico'; // Path to the smaller logo

            const handleScroll = () => {
                // Check if the window width is less than or equal to 720px
                if (window.innerWidth <= 720) {
                    if (window.scrollY > 50) { // Change this value to adjust when to shrink
                        navbar.classList.add('scrolled');
                        logo.src = smallerLogoSrc; // Change the logo when scrolled
                    } else {
                        navbar.classList.remove('scrolled');
                        logo.src = originalLogoSrc; // Revert back to the original logo
                    }
                } else {
                    // Reset navbar and logo if on larger screens
                    navbar.classList.remove('scrolled');
                    logo.src = originalLogoSrc;
                }
            };

            window.addEventListener('scroll', handleScroll);
            window.addEventListener('resize', handleScroll); // Call handleScroll on resize
        });
    </script> -->



 <script>
     $(function() {
         // Check if dark mode is already set in localStorage
         if (localStorage.getItem('mode') === 'light') {
             $('body').addClass('light-mode').removeClass('dark-mode');
             $('#theme-icon').html('<img src="/assets/img/moon.png" width="20px" height="20px" alt="Light Mode">');
             $('#mode-text').text('Light ');
         } else {
             $('body').addClass('dark-mode').removeClass('light-mode');
             $('#theme-icon').html('<img src="/assets/img/sun.png" width="20px" height="20px" alt="Dark Mode">');
             $('#mode-text').text('Dark ');
         }

         // Toggle between light and dark modes when the button is clicked
         $('#toggle-mode').click(function() {
             $('body').toggleClass('dark-mode light-mode');

             // Save the mode in localStorage and update the icon/text
             if ($('body').hasClass('light-mode')) {
                 localStorage.setItem('mode', 'light');
                 $('#theme-icon').html('<img src="/assets/img/moon.png" width="20px" height="20px" alt="Light Mode">');
                 $('#mode-text').text('Light ');
             } else {
                 localStorage.setItem('mode', 'dark');
                 $('#theme-icon').html('<img src="/assets/img/sun.png" width="20px" height="20px" alt="Dark Mode">');
                 $('#mode-text').text('Dark ');
             }
         });
     });
 </script>






 <script>
     let lastScrollTop = 0; // Store last scroll position
     const navbar = document.querySelector('.navbar'); // Get the navbar element

     // Function to check if it's mobile view (you can adjust the breakpoint)
     function isMobile() {
         return window.innerWidth <= 768;
     }
     window.addEventListener('scroll', function() {
         if (isMobile()) { // Only apply the effect on mobile
             let currentScroll = window.scrollY;

             if (currentScroll > lastScrollTop) {
                 navbar.style.transform = 'translateY(-100%)';
             } else {
                 navbar.style.transform = 'translateY(0)';
             }

             lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
         }
     });

     // You can also check if window is resized and reapply the logic
     window.addEventListener('resize', function() {
         if (!isMobile()) {
             // Reset navbar position when the screen size goes above mobile view
             navbar.style.transform = 'translateY(0)';
         }
     });
 </script>