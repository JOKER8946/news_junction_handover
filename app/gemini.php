<?php


require __DIR__ . '/vendor/autoload.php';

// database 
$servername = "localhost";
$username = "YOUR_DB_USER";
$password = "YOUR_DB_PASSWORD";
$dbname = "cream";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);
// =========== 


// $email = $_GET['email'];
$api_key = "YOUR_GOOGLE_API_KEY";

// $result = $conn->query("SELECT * FROM creamNow ") or die("Database Error: " . $conn->error);
// $user = $result->fetch_assoc();
$result = $conn->query("SELECT * FROM creamNow ORDER BY submissionDate DESC LIMIT 1") or die("Database Error: " . $conn->error);
$user = $result->fetch_assoc();

// Function to detect gender from user's name
function detectGenderFromName($name)
{
    // Common Indian male names
    $maleNames = [
        'raj',
        'amit',
        'rahul',
        'vijay',
        'suresh',
        'arun',
        'deepak',
        'rajesh',
        'sanjay',
        'kumar',
        'aditya',
        'rohit',
        'vikram',
        'arjun',
        'ravi',
        'ajay',
        'vivek',
        'prakash',
        'akash',
        'nikhil',
        'varun',
        'anand',
        'dhruv',
        'gaurav',
        'manish',
        'mohit',
        'naveen',
        'pranav',
        'sachin'
    ];

    // Common Indian female names
    $femaleNames = [
        'priya',
        'neha',
        'pooja',
        'anjali',
        'shreya',
        'divya',
        'kavita',
        'ritu',
        'swati',
        'aishwarya',
        'sneha',
        'nisha',
        'manisha',
        'anita',
        'meera',
        'rekha',
        'jyoti',
        'sunita',
        'radha',
        'deepika',
        'aarti',
        'geeta',
        'komal',
        'preeti',
        'shweta',
        'tanvi',
        'usha',
        'vandana'
    ];

    // Get the first name (in case full name is provided)
    $firstName = strtolower(explode(' ', $name)[0]);

    // Check if the name is in the male list
    if (in_array($firstName, $maleNames)) {
        return 'male';
    }

    // Check if the name is in the female list
    if (in_array($firstName, $femaleNames)) {
        return 'female';
    }

    // If not found in either list, do a broader check
    foreach ($maleNames as $maleName) {
        if (strpos($firstName, $maleName) === 0) {
            return 'male';
        }
    }

    foreach ($femaleNames as $femaleName) {
        if (strpos($firstName, $femaleName) === 0) {
            return 'female';
        }
    }

    // Default case
    return 'unknown';
}

// Function to get random Indian name based on gender
function getRandomIndianName($gender)
{
    $maleNames = [
        'Raj Kumar',
        'Amit Sharma',
        'Rahul Patel',
        'Vijay Singh',
        'Suresh Verma',
        'Arun Gupta',
        'Deepak Joshi',
        'Rajesh Khanna',
        'Sanjay Mehta',
        'Vikram Malhotra',
        'Arjun Nair',
        'Ravi Krishnan',
        'Ajay Reddy',
        'Vivek Iyer',
        'Prakash Thakur',
        'Akash Kapoor',
        'Nikhil Choudhury',
        'Varun Agarwal',
        'Anand Desai',
        'Dhruv Banerjee'
    ];

    $femaleNames = [
        'Priya Sharma',
        'Neha Patel',
        'Pooja Singh',
        'Anjali Verma',
        'Shreya Gupta',
        'Divya Joshi',
        'Kavita Khanna',
        'Ritu Mehta',
        'Swati Malhotra',
        'Aishwarya Nair',
        'Sneha Krishnan',
        'Nisha Reddy',
        'Manisha Iyer',
        'Anita Thakur',
        'Meera Kapoor',
        'Rekha Choudhury',
        'Jyoti Agarwal',
        'Sunita Desai',
        'Radha Banerjee',
        'Deepika Sinha'
    ];

    if ($gender === 'male') {
        return $maleNames[array_rand($maleNames)];
    } else {
        return $femaleNames[array_rand($femaleNames)];
    }
}

// Function to get random avatar based on gender
function getRandomAvatar($gender)
{
    if ($gender === 'male') {
        return "avatars/" . rand(1, 4) . ".png";
    } else {
        return "avatars/" . rand(5, 7) . ".png";
    }
}

// Determine gender from user's name in database
$userGender = 'unknown';
if (isset($user['name']) && !empty($user['name'])) {
    $userGender = detectGenderFromName($user['name']);
}

// If gender couldn't be determined, use a default
if ($userGender === 'unknown') {
    $userGender = 'male'; // Default gender
}

// Generate persona name based on detected gender
$personaName = getRandomIndianName($userGender);

$personaInput = "Product: " . $user['productName'] .
    // "\nChallenges: " . $user['challenges'] .
    // "\nconversation: " . $user['conversation'] .
    // "\nAssets: " . $user['assets'] .
    // "\nSocial Media: " . $user['social_media'] .
    "\nPersona Name: " . $personaName .
    "\nGenerate a persona with this Indian name and cultural context. Format the output for easy reading. Use bold tags for headings and complete persona.";


$industryReportInput = "Product: " . $user['productName'] . "\nBased on the industry, create a report on where the business stands today and also the heading of the report should be in bold letters and should come in new line and give the complete report.";

$list_models_url = "https://generativelanguage.googleapis.com/v1beta/models?key=$api_key";

$ch_list = curl_init();
curl_setopt($ch_list, CURLOPT_URL, $list_models_url);
curl_setopt($ch_list, CURLOPT_RETURNTRANSFER, true);

$list_models_response = curl_exec($ch_list);
$list_models_http_code = curl_getinfo($ch_list, CURLINFO_HTTP_CODE);
curl_close($ch_list);

$persona_text = "⚠️ No valid persona response from Gemini.";
$industry_report_text = "⚠️ No valid industry report response from Gemini.";

if ($list_models_http_code == 200) {
    $list_models_data = json_decode($list_models_response, true);
    $gemini_flash_model = null;

    foreach ($list_models_data['models'] as $model) {
        if (strpos($model['name'], 'gemini-') !== false && strpos($model['name'], '-flash') !== false && in_array('generateContent', $model['supportedGenerationMethods'])) {
            $gemini_flash_model = $model['name'];
            break;
        }
    }

    if ($gemini_flash_model) {
        // Persona Generation
        $persona_data = [
            "model" => $gemini_flash_model,
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => "Generate a persona based on the following information:\n" . $personaInput
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 500
            ]
        ];

        $persona_url = "https://generativelanguage.googleapis.com/v1beta/" . $gemini_flash_model . ":generateContent?key=$api_key";

        $ch_persona = curl_init();
        curl_setopt($ch_persona, CURLOPT_URL, $persona_url);
        curl_setopt($ch_persona, CURLOPT_POST, true);
        curl_setopt($ch_persona, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch_persona, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_persona, CURLOPT_POSTFIELDS, json_encode($persona_data));

        $persona_response = curl_exec($ch_persona);
        $persona_http_code = curl_getinfo($ch_persona, CURLINFO_HTTP_CODE);
        curl_close($ch_persona);

        if ($persona_response === false) {
            $persona_text = "❌ cURL Error: " . curl_error($ch_persona);
        } else if ($persona_http_code != 200) {
            $persona_text = "❌ API Error: HTTP Status Code $persona_http_code - Response: " . $persona_response;
        } else {
            $gemini_persona_data = json_decode($persona_response, true);
            if (isset($gemini_persona_data['candidates'][0]['content']['parts'][0]['text'])) {
                $persona_text = $gemini_persona_data['candidates'][0]['content']['parts'][0]['text'];

                // Get avatar based on detected gender from user's name
                $avatarImage = getRandomAvatar($userGender);

                // Clean up the text as before
                $persona_text = str_replace(["<br />\r\n", "<br />\n", "<br />"], "", $persona_text);
                $Parsedown = new Parsedown();
                $persona_text = $Parsedown->text($persona_text);
                $persona_text = preg_replace('/<strong>\s*<\/strong>/', '', $persona_text);
                $persona_text = preg_replace('/<em>\s*<\/em>/', '', $persona_text);

                // Add the avatar to the output
                $persona_text = '<div class="persona-container">
                    <img src="' . htmlspecialchars($avatarImage) . '" alt="Persona Avatar" class="avatar-image">
                    <div class="persona-content">' . $persona_text . '</div>
                </div>';
            }
        }

        // Industry Report Generation
        $industry_report_data = [
            "model" => $gemini_flash_model,
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => "Generate an industry report based on the following information:\n" . $industryReportInput
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 500
            ]
        ];

        $industry_report_url = "https://generativelanguage.googleapis.com/v1beta/" . $gemini_flash_model . ":generateContent?key=$api_key";

        $ch_industry_report = curl_init();
        curl_setopt($ch_industry_report, CURLOPT_URL, $industry_report_url);
        curl_setopt($ch_industry_report, CURLOPT_POST, true);
        curl_setopt($ch_industry_report, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch_industry_report, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_industry_report, CURLOPT_POSTFIELDS, json_encode($industry_report_data));

        $industry_report_response = curl_exec($ch_industry_report);
        $industry_report_http_code = curl_getinfo($ch_industry_report, CURLINFO_HTTP_CODE);
        curl_close($ch_industry_report);

        if ($industry_report_response === false) {
            $industry_report_text = "❌ cURL Error: " . curl_error($ch_industry_report);
        } else if ($industry_report_http_code != 200) {
            $industry_report_text = "❌ API Error: HTTP Status Code $industry_report_http_code - Response: " . $industry_report_response;
        } else {
            $gemini_industry_report_data = json_decode($industry_report_response, true);
            if (isset($gemini_industry_report_data['candidates'][0]['content']['parts'][0]['text'])) {
                $industry_report_text = $gemini_industry_report_data['candidates'][0]['content']['parts'][0]['text'];

                // Remove line break <br> tags
                $industry_report_text = str_replace(["<br />\r\n", "<br />\n", "<br />"], "", $industry_report_text);

                // Use Parsedown for Markdown to HTML conversion
                $Parsedown = new Parsedown();
                $industry_report_text = $Parsedown->text($industry_report_text);

                // Remove empty strong/em tags
                $industry_report_text = preg_replace('/<strong>\s*<\/strong>/', '', $industry_report_text);
                $industry_report_text = preg_replace('/<em>\s*<\/em>/', '', $industry_report_text);
            }
        }
    } else {
        die("❌ No Gemini Flash model with generateContent support found.");
    }
} else {
    die("Error listing models: HTTP Status Code $list_models_http_code - Response: $list_models_response");
}

$conn->close();

$industry = strtolower($user['industry']);
function getIndustryData($industry)
{
    $data = [];
    switch ($industry) {
        case 'saas':
            $data['growth'] = [2.6, 3.5, 4.5];
            $data['description'] = "SaaS: Software as a Service (SaaS) allows users to connect to and use cloud-based apps over the Internet. Common examples include email, calendaring, and office tools like Microsoft Office 365. SaaS provides a complete software solution that you purchase on a pay-as-you-go basis from a cloud service provider. The underlying infrastructure, middleware, app software, and app data are located in the service provider's data center. The service provider manages the hardware and software, ensuring the availability and security of the app and your data. SaaS enables organizations to get quickly up and running with an app at minimal upfront cost. Users can access SaaS apps and data from any Internet-connected computer or mobile device, making it easy to mobilize the workforce. SaaS also allows access to sophisticated applications without the need to purchase, install, update, or maintain any hardware, middleware, or software.";
            break;
        case 'retail':
            $data['growth'] = [1.2, 1.5, 1.8];
            $data['description'] = "Retail: Retail is the process of selling goods or services directly to consumers for personal use. It encompasses a wide range of businesses, from small local shops to large multinational corporations and online marketplaces. Retailers act as intermediaries between manufacturers and customers, making products easily accessible through physical stores, e-commerce platforms, or a combination of both. The industry is highly dynamic and customer-centric, relying on marketing strategies, competitive pricing, and technological advancements to enhance the shopping experience. With the rise of digital transformation, online shopping, personalized recommendations, and automation have become integral to modern retail. Businesses are also focusing on sustainability and ethical practices to meet changing consumer demands. The retail landscape continues to evolve, adapting to trends such as omnichannel retailing, direct-to-consumer brands, and AI-driven customer engagement.";
            break;
        case 'technology':
            $data['growth'] = [5.0, 5.8, 6.7];
            $data['description'] = "Technology:Technology refers to the application of scientific knowledge to create practical tools and systems, fundamentally shaping how we live, work, and communicate today. From smartphones in our pockets to advanced medical devices, technology has revolutionized various sectors like healthcare, transportation, and education, making information readily accessible and tasks more efficient. While offering immense benefits, ethical considerations regarding data privacy and potential job displacement due to automation remain important aspects to manage in the evolving technological landscape.";
            break;
        case 'finance':
            $data['growth'] = [2.2, 2.8, 3.3];
            $data['description'] = "Finance: Finance is a term that addresses matters regarding the management, creation, and study of money and investments. It involves the use of credit and debt, securities, and investment to finance current projects using future income flows. Finance is closely linked to the time value of money, interest rates, and other related topics because of this temporal aspect.";
            break;
        case 'healthcare':
            $data['growth'] = [3.5, 4.1, 4.8];
            $data['description'] = "Healthcare: Healthcare encompasses services aimed at maintaining and improving health, including prevention, diagnosis, treatment, and rehabilitation of illnesses and injuries, delivered by healthcare professionals. It's a fundamental need, crucial for a healthy and productive society, and can significantly impact a country's economy and development.";
            break;
        case 'manufacturing':
            $data['growth'] = [1.8, 2.1, 2.4];
            $data['description'] = "Manufacturing: Manufacturing is the process of producing goods by transforming raw materials into finished products using labor, tools, machinery, and chemical or biological processes. It plays a critical role in various industries, including automotive, electronics, textiles, and food production. Manufacturers create products on a large scale, often using standardized procedures, in order to meet market demands.";
            break;
        case 'education':
            $data['growth'] = [2.0, 2.3, 2.6];
            $data['description'] = "Education:Education is the process of acquiring knowledge, skills, values, and attitudes through various methods, such as formal schooling, training, or self-learning. It aims to equip individuals with the ability to think critically, solve problems, and contribute effectively to society. Education can take place in various settings, including schools, colleges, universities, online platforms, and through informal means like mentorship or community learning. It is fundamental for personal development, social mobility, and economic progress.";
            break;
        case 'construction':
            $data['growth'] = [1.5, 1.7, 1.9];
            $data['description'] = "Construction: Construction is the process of building infrastructure, buildings, and other structures. It involves planning, design, and execution using various materials and techniques.";
            break;
        case 'transportation':
            $data['growth'] = [1.9, 2.2, 2.5];
            $data['description'] = "Transportation: Transportation is the movement of people, goods, and services from one location to another. It plays a crucial role in economic development, trade, and daily life. The main modes of transportation include road, rail, air, water, and pipeline. Road transportation includes cars, buses, and trucks, making it the most widely used mode. Rail transportation is efficient for moving heavy goods and passengers over long distances. Air transportation is the fastest but also the most expensive mode. Water transportation is mainly used for shipping large goods internationally. Pipelines are used for transporting liquids and gases like oil and natural gas. Advancements in technology have improved transportation efficiency and sustainability. Future developments focus on electric vehicles, high-speed trains, and smart transportation systems.";
            break;
        case 'energy':
            $data['growth'] = [2.3, 2.6, 2.9];
            $data['description'] = "Energy: Energy is the ability to do work and is essential for all activities in daily life. It exists in various forms, including kinetic, potential, thermal, chemical, electrical, and nuclear energy. The two main types of energy sources are renewable and non-renewable. Renewable energy sources include solar, wind, hydro, and geothermal, which are sustainable and environmentally friendly. Non-renewable sources, such as coal, oil, and natural gas, are limited and contribute to pollution. Energy is crucial for industries, transportation, and households. Efficient energy use helps reduce waste and environmental impact. Advances in technology are promoting cleaner and more efficient energy solutions. The future of energy lies in sustainability and innovation to meet growing global demands.";
            break;
        case 'agriculture':
            $data['growth'] = [1.0, 1.2, 1.4];
            $data['description'] = "Agriculture: Agriculture is the practice of cultivating crops and raising livestock for food, fiber, and other products. It is one of the oldest human activities and remains essential for survival and economic development. There are different types of agriculture, including subsistence farming, commercial farming, organic farming, and agroforestry. Modern agriculture relies on technology, irrigation, fertilizers, and machinery to increase productivity. Sustainable farming practices help protect soil, water, and biodiversity. Livestock farming provides meat, dairy, and wool, while crop farming produces grains, vegetables, and fruits. Climate change and resource depletion pose challenges to agriculture. Innovations like vertical farming and precision agriculture improve efficiency. The future of agriculture focuses on sustainability, automation, and food security.";
            break;
        case 'media':
            $data['growth'] = [2.8, 3.1, 3.4];
            $data['description'] = "Media: Media is the means of communication used to disseminate information, entertainment, and messages to a wide audience. It plays a significant role in shaping public perception, influencing opinions, and connecting people across the world. Traditionally, media included newspapers, magazines, radio, and television, which were the primary sources of news and entertainment. With the rise of the internet, digital media has transformed the way content is created and consumed, making information more accessible and interactive. Social media platforms have further revolutionized communication by allowing individuals to share their thoughts, opinions, and experiences instantly. The media industry also serves as a powerful tool for businesses, governments, and organizations to market their products, convey messages, and engage with their audiences. As technology continues to evolve, media is becoming more personalized, data-driven, and immersive, shaping the way people interact with content in their daily lives.";
            break;
        case 'telecommunications':
            $data['growth'] = [3.2, 3.5, 3.8];
            $data['description'] = "Telecommunications: Telecommunications refers to the transmission of information over long distances through electronic means, enabling communication between individuals, businesses, and systems. It includes technologies such as telephone networks, mobile communications, radio, television, and the internet. Over time, telecommunications has evolved from traditional wired systems like telegraphs and landline phones to advanced wireless technologies, including fiber optics, satellite communication, and 5G networks. The industry plays a crucial role in modern society by facilitating instant communication, supporting global business operations, and enabling access to information and digital services. With the rise of smartphones and high-speed internet, telecommunications has become an essential part of daily life, driving innovations in cloud computing, artificial intelligence, and the Internet of Things (IoT). As technology continues to advance, the sector is focused on improving connectivity, increasing data speeds, and enhancing security to meet the growing demands of an interconnected world.";
            break;
        case 'real estate':
            $data['growth'] = [1.7, 2.0, 2.3];
            $data['description'] = "Real Estate: As per the definition of Real Estate Investment Advisory Services, real estate refers to physical property encompassing land and any permanent structures or constructions built above or below it. This includes everything from residential homes and commercial buildings to infrastructure and developments that enhance the value of the land. In essence, real estate is any tangible asset tied to the land.";
            break;
        case 'hospitality':
            $data['growth'] = [2.1, 2.4, 2.7];
            $data['description'] = "Hospitality: Hospitality is the relationship of a host towards a guest, wherein the host receives the guest with some amount of goodwill and welcome. This includes the reception and entertainment of guests, visitors, or strangers. Louis, chevalier de Jaucourt describes hospitality in the Encyclopédie as the virtue of a great soul that cares for the whole universe through the ties of humanity.[4] Hospitality is also the way people treat others, for example in the service of welcoming and receiving guests in hotels. Hospitality plays a role in augmenting or decreasing the volume of sales of an organization.";
            break;
        case 'pharmaceuticals':
            $data['growth'] = [3.8, 4.2, 4.6];
            $data['description'] = "Pharmaceuticals refer to medicines and drugs used for the diagnosis, treatment, and prevention of diseases. The pharmaceutical industry researches, develops, manufactures, and distributes medications to improve health and well-being. There are two main types of drugs: prescription medications, which require a doctor's approval, and over-the-counter (OTC) drugs, which can be purchased without a prescription. Pharmaceutical research involves drug discovery, clinical trials, and regulatory approvals to ensure safety and effectiveness. Biotechnology and genetic research have led to advancements in personalized medicine. The industry is regulated by organizations like the FDA (USA) and WHO globally. Generic drugs provide affordable alternatives to brand-name medications. The pharmaceutical sector plays a vital role in global healthcare. Ongoing innovations focus on vaccines, gene therapy, and drug delivery systems.";
            break;
        case 'automotive':
            $data['growth'] = [1.6, 1.9, 2.2];
            $data['description'] = "Automotive: The automotive industry focuses on designing, manufacturing, and selling vehicles such as cars, trucks, motorcycles, and buses. It is one of the largest industries in the world, driving economic growth and technological advancements. Automobiles are powered by various energy sources, including gasoline, diesel, electricity, and hydrogen. Modern vehicles incorporate advanced safety features, automation, and smart technology. Electric and hybrid cars are gaining popularity due to environmental concerns and fuel efficiency. The industry is shifting towards autonomous vehicles and sustainable transportation solutions. Major automotive companies include Toyota, Ford, BMW, and Tesla. Regular vehicle maintenance ensures performance, safety, and longevity. Innovations in materials and aerodynamics improve fuel efficiency and reduce emissions. The future of the automotive industry focuses on automation, connectivity, and sustainability.";
            break;
        case 'aerospace':
            $data['growth'] = [2.5, 2.8, 3.1];
            $data['description'] = "Aerospace:Aerospace is a term used to collectively refer to the atmosphere and outer space. Aerospace activity is very diverse, with a multitude of commercial, industrial, and military applications. Aerospace engineering consists of aeronautics and astronautics. Aerospace organizations research, design, manufacture, operate, maintain, and repair both aircraft and spacecraft.The beginning of space and the ending of the air are proposed as 100km (62mi) above the ground according to the physical explanation that the air density is too low for a lifting body to generate meaningful lift force without exceeding orbital velocity";
            break;
        case 'chemical':
            $data['growth'] = [1.4, 1.7, 2.0];
            $data['description'] = "Chemical: A chemical is any substance with a defined composition, meaning it's made up of specific elements in a set ratio, giving it unique properties. Chemicals can be naturally occurring like water (H2O) or man-made like plastics. They can exist as single elements (like oxygen) or compounds (like sodium chloride, common salt). Chemical reactions occur when chemicals interact, rearranging their atoms to form new substances, often accompanied by changes in temperature, color, or state. Understanding chemistry, the study of chemicals, is crucial for fields like medicine, materials science, and environmental protection, allowing us to develop new technologies and solve complex problems.";
            break;
        case 'mining':
            $data['growth'] = [1.1, 1.3, 1.5];
            $data['description'] = "Mining: Mining is the extraction of valuable geological materials and minerals from the surface of the Earth. Mining is required to obtain most materials that cannot be grown through agricultural processes, or feasibly created artificially in a laboratory or factory. Ores recovered by mining include metals, coal, oil shale, gemstones, limestone, chalk, dimension stone, rock salt, potash, gravel, and clay. The ore must be a rock or mineral that contains valuable constituent, can be extracted or mined and sold for profit.[1] Mining in a wider sense includes extraction of any non-renewable resource such as petroleum, natural gas, or even water.";
            break;
        case 'insurance':
            $data['growth'] = [2.9, 3.2, 3.5];
            $data['description'] = "Insurance: Insurance is a means of protection from financial loss in which, in exchange for a fee, a party agrees to compensate another party in the event of a certain loss, damage, or injury. It is a form of risk management, primarily used to protect against the risk of a contingent or uncertain loss.";
            break;
        case 'consulting':
            $data['growth'] = [3.3, 3.6, 3.9];
            $data['description'] = "Consulting:Consulting is a professional service that provides expert advice, guidance, and solutions to businesses, organizations, and individuals to help them improve performance, solve problems, and achieve their goals. Consultants are specialists in their fields, offering insights based on industry experience, research, and analysis. Consulting can cover various areas, including management, finance, technology, marketing, human resources, and strategy. Firms and independent consultants work closely with clients to assess challenges, develop strategies, and implement changes that drive growth and efficiency. The industry plays a vital role in helping businesses navigate market changes, optimize operations, and adopt new technologies. With the rise of digital transformation, consulting has evolved to include data-driven decision-making, automation, and AI-driven insights, making it an essential service for organizations seeking to stay competitive in a rapidly changing world.";
            break;
        case 'logistics':
            $data['growth'] = [2.7, 3.0, 3.3];
            $data['description'] = "Logistics: Logistics is the part of supply chain management that deals with the efficient forward and reverse flow of goods, services, and related information from the point of origin to the point of consumption according to the needs of customers.[2][3] Logistics management is a component that holds the supply chain together.[3] The resources managed in logistics may include tangible goods such as materials, equipment, and supplies, as well as food and other consumable items.";
            break;
        case 'utilities':
            $data['growth'] = [1.3, 1.6, 1.9];
            $data['description'] = "Utilities: Utilities refer to essential services that provide basic infrastructure necessary for daily life, including electricity, water, natural gas, and telecommunications. These services are typically managed by government entities, private companies, or a combination of both, ensuring that homes, businesses, and industries have access to reliable resources. The utility sector plays a crucial role in economic development and public welfare by maintaining and upgrading infrastructure, managing supply and demand, and adopting sustainable practices. With the growing focus on renewable energy, smart grids, and water conservation, the industry is evolving to integrate advanced technologies and environmentally friendly solutions. As consumer needs change and regulatory requirements increase, utility providers continue to innovate to enhance efficiency, reduce costs, and improve service reliability.";
            break;
        case 'textiles':
            $data['growth'] = [1.2, 1.4, 1.6];
            $data['description'] = "Textiles: Textiles refer to materials made from natural or synthetic fibers that are woven, knitted, or processed to create fabrics used in clothing, home furnishings, industrial applications, and more. The textile industry encompasses the production of raw fibers like cotton, wool, silk, and polyester, as well as processes such as spinning, dyeing, printing, and finishing. Over time, the industry has evolved with technological advancements, leading to the development of high-performance fabrics, sustainable textiles, and smart materials with enhanced properties like water resistance and breathability. Fashion, interior design, and manufacturing heavily rely on textiles, making the industry a significant contributor to global trade and employment. With increasing concerns about sustainability, there is a growing emphasis on eco-friendly materials, ethical production methods, and recycling initiatives to reduce environmental impact while maintaining innovation and quality in textile manufacturing.";
            break;
        default:
            $data['growth'] = [3.0, 3.3, 3.7];
            $data['description'] = "General industry trends indicate steady growth across various sectors, driven by technological advancements, evolving consumer preferences, and global economic expansion. Industries such as technology, healthcare, renewable energy, and e-commerce are experiencing rapid innovation, while traditional sectors like manufacturing and retail are adapting to digital transformation and automation. The increasing focus on sustainability, artificial intelligence, and data-driven decision-making is reshaping business models, leading to greater efficiency and competitiveness. Additionally, globalization and supply chain advancements have enabled businesses to expand their reach, while shifting workforce dynamics, such as remote work and gig economies, are influencing employment patterns. Despite occasional economic uncertainties and regulatory challenges, industries continue to evolve, leveraging new opportunities to maintain consistent and sustainable growth.";

            break;
    }
    return $data;
}

$industryData = getIndustryData($industry);

$chartData = [
    'labels' => ['2021', '2022', '2023'],
    'datasets' => [
        [
            'label' => $user['industry'] . ' Industry Growth (USD Billion)',
            'data' => $industryData['growth'],
            'backgroundColor' => ['rgba(75, 192, 192, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)'],
            'borderColor' => ['rgba(75, 192, 192, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)'],
            'borderWidth' => 1,
        ]
    ],
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industry Insights</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .persona-container {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .avatar-image {
            width: 75px;
            /* Passport photo size */
            height: 75px;
            /* Passport photo size */
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .persona-content {
            flex: 1;
            font-size: 14px;
            line-height: 1.5;
        }

        .container {
            width: 50%;
            margin: auto;
            padding: 20px;
            background: #f4f4f4;
            border-radius: 10px;
            box-shadow: 0px 0px 10px gray;
        }

        input,
        textarea {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            background: #28a745;
            color: white;
            padding: 10px;
            border: none;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #218838;
        }

        .persona-container {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .avatar-image {
            width: 35px;
            /* Passport photo size */
            height: 35px;
            /* Passport photo size */
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .persona-content {
            flex: 1;
            font-size: 14px;
            line-height: 1.5;
        }

        @media screen and (max-width:540px) {
            .container {
            width: 100% !important;
            margin: auto;
            padding: 20px;
            background: #f4f4f4;
            border-radius: 10px;
            box-shadow: 0px 0px 10px gray;
        }
            
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <canvas id="myChart" width="400" height="200"></canvas>
        <h2>Industry Analysis: <?= htmlspecialchars($user['industry']) ?></h2>
        <p><strong>Industry Description:</strong></p>
        <p><?= htmlspecialchars($industryData['description']) ?></p>

        <h2>Generated Persona</h2>

        <p><?= $persona_text ?></p>


        <h2>Industry Report</h2>
        <p><?= $industry_report_text ?></p>


        <!-- <a href="index.html">Go Back</a> -->
    </div>

    <script>
        const chartData = <?php echo json_encode($chartData); ?>;

        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: chartData.datasets[0].label,
                    },
                    legend: {
                        display: true,
                    },
                }
            }
        });
    </script>
</body>

</html>