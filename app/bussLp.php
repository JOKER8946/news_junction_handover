<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnoblyCream</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.7.1/gsap.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

        /* Hero Section Base Styles */
        .hero-section {
            position: relative;
            padding: 80px 0;
            min-height: 100vh;
            background-color: #fef6f3;
            overflow: hidden;
        }

        /* Noise Background */
        .noise-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.4;
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAMAAAAp4XiDAAAAUVBMVEWFhYWDg4N3d3dtbW17e3t1dXWBgYGHh4d5eXlzc3OLi4ubm5uVlZWPj4+NjY19fX2JiYl/f39ra2uRkZGZmZlpaWmXl5dvb29xcXGTk5NnZ2c8TV1mAAAAG3RSTlNAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEAvEOwtAAAFVklEQVR4XpWWB67c2BUFb3g557T/hRo9/WUMZHlgr4Bg8Z4qQgQJlHI4A8SzFVrapvmTF9O7dmYRFZ60YiBhJRCgh1FYhiLAmdvX0CzTOpNE77ME0Zty/nWWzchDtiqrmQDeuv3powQ5ta2eN0FY0InkqDD73lT9c9lEzwUNqgFHs9VQce3TVClFCQrSTfOiYkVJQBmpbq2L6iZavPnAPcoU0dSw0SUTqz/GtrGuXfbyyBniKykOWQWGqwwMA7QiYAxi+IlPdqo+hYHnUt5ZPfnsHJyNiDtnpJyayNBkF6cWoYGAMY92U2hXHF/C1M8uP/ZtYdiuj26UdAdQQSXQErwSOMzt/XWRWAz5GuSBIkwG1H3FabJ2OsUOUhGC6tK4EMtJO0ttC6IBD3kM0ve0tJwMdSfjZo+EEISaeTr9P3wYrGjXqyC1krcKdhMpxEnt5JetoulscpyzhXN5FRpuPHvbeQaKxFAEB6EN+cYN6xD7RYGpXpNndMmZgM5Dcs3YSNFDHUo2LGfZuukSWyUYirJAdYbF3MfqEKmjM+I2EfhA94iG3L7uKrR+GdWD73ydlIB+6hgref1QTlmgmbM3/LeX5GI1Ux1RWpgxpLuZ2+I+IjzZ8wqE4nilvQdkUdfhzI5QDWy+kw5Wgg2pGpeEVeCCA7b85BO3F9DzxB3cdqvBzWcmzbyMiqhzuYqtHRVG2y4x+KOlnyqla8AoWWpuBoYRxzXrfKuILl6SfiWCbjxoZJUaCBj1CjH7GIaDbc9kqBY3W/Rgjda1iqQcOJu2WW+76pZC9QG7M00dffe9hNnseupFL53r8F7YHSwJWUKP2q+k7RdsxyOB11n0xtOvnW4irMMFNV4H0uqwS5ExsmP9AxbDTc9JwgneAT5vTiUSm1E7BSflSt3bfa1tv8Di3R8n3Af7MNWzs49hmauE2wP+ttrq+AsWpFG2awvsuOqbipWHgtuvuaAE+A1Z/7gC9hesnr+7wqCwG8c5yAg3AL1fm8T9AZtp/bbJGwl1pNrE7RuOX7PeMRUERVaPpEs+yqeoSmuOlokqw49pgomjLeh7icHNlG19yjs6XXOMedYm5xH2YxpV2tc0Ro2jJfxC50ApuxGob7lMsxfTbeUv07TyYxpeLucEH1gNd4IKH2LAg5TdVhlCafZvpskfncCfx8pOhJzd76bJWeYFnFciwcYfubRc12Ip/ppIhA1/mSZ/RxjFDrJC5xifFjJpY2Xl5zXdguFqYyTR1zSp1Y9p+tktDYYSNflcxI0iyO4TPBdlRcpeqjK/piF5bklq77VSEaA+z8qmJTFzIWiitbnzR794USKBUaT0NTEsVjZqLaFVqJoPN9ODG70IPbfBHKK+/q/AWR0tJzYHRULOa4MP+W/HfGadZUbfw177G7j/OGbIs8TahLyynl4X4RinF793Oz+BU0saXtUHrVBFT/DnA3ctNPoGbs4hRIjTok8i+algT1lTHi4SxFvONKNrgQFAq2/gFnWMXgwffgYMJpiKYkmW3tTg3ZQ9Jq+f8XN+A5eeUKHWvJWJ2sgJ1Sop+wwhqFVijqWaJhwtD8MNlSBeWNNWTa5Z5kPZw5+LbVT99wqTdx29lMUH4OIG/D86ruKEauBjvH5xy6um/Sfj7ei6UUVk4AIl3MyD4MSSTOFgSwsH/QJWaQ5as7ZcmgBZkzjjU1UrQ74ci1gWBCSGHtuV1H2mhSnO3Wp/3fEV5a+4wz//6qy8JxjZsmxxy5+4w9CDNJY09T072iKG0EnOS0arEYgXqYnXcYHwjTtUNAcMelOd4xpkoqiTYICWFq0JSiPfPDQdnt+4/wuqcXY47QILbgAAAABJRU5ErkJggg==');
        }

        /* Gradient Spheres */
        .gradient-sphere {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
        }

        .sphere-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #db5919 0%, #ff8f59 100%);
            top: -100px;
            right: -100px;
            opacity: 0.15;
        }

        .sphere-2 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #ff8f59 0%, #ffdcc8 100%);
            bottom: -50px;
            left: -50px;
            opacity: 0.1;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        /* Hero Content */
        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }

        /* Hero Badge */
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: rgba(219, 89, 25, 0.1);
            padding: 8px 16px;
            border-radius: 100px;
            margin-bottom: 24px;
        }

        .badge-icon {
            width: 20px;
            height: 20px;
        }

        /* Hero Title */
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 24px;
            color: #1a1a1a;
        }

        .gradient-text {
            background: linear-gradient(135deg, #db5919 0%, #ff8f59 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Hero Description */
        .hero-description {
            font-size: 1.125rem;
            line-height: 1.6;
            color: #4a4a4a;
            margin-bottom: 32px;
            max-width: 540px;
        }

        /* CTA Buttons */
        .cta-group {
            display: flex;
            gap: 16px;
            margin-bottom: 48px;
        }

        .primary-button {
            background: linear-gradient(135deg, #db5919 0%, #ff8f59 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .primary-button:hover {
            transform: translateY(-2px);
        }

        .demo-button {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: transparent;
            color: #db5919;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: 2px solid rgba(219, 89, 25, 0.2);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .demo-button:hover {
            background-color: rgba(219, 89, 25, 0.1);
        }

        /* Stats Row */
        .stats-row {
            display: flex;
            gap: 48px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #db5919;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #4a4a4a;
        }

        /* Hero Image */
        .hero-image-wrapper {
            position: relative;
        }

        .hero-image {
            width: 100%;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(219, 89, 25, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-description {
                margin: 0 auto 32px;
            }

            .cta-group {
                justify-content: center;
            }

            .stats-row {
                justify-content: center;
            }

            .hero-image-wrapper {
                margin-top: 40px;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .stats-row {
                flex-direction: column;
                align-items: center;
                gap: 24px;
            }
        }
    </style>
    <style>
        .gradient-text {
            background: linear-gradient(45deg, #db5919, #06B6D4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(79, 70, 229, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .glow {
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 5px rgba(79, 70, 229, 0.2);
            }

            to {
                box-shadow: 0 0 20px rgba(79, 70, 229, 0.4);
            }
        }
    </style>



    <!-- feature section css -->
    <!-- <style>
        /* Features Section Styles */
        .features-section {
            position: relative;
            padding: 120px 0;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9ff 100%);
            overflow: hidden;
        }

        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(138, 143, 255, 0.2) 50%, transparent 100%);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .gradient-heading {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
            position: relative;
            display: inline-block;
        }

        .gradient-heading::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #d946ef);
            border-radius: 2px;
        }

        .section-subtext {
            font-size: 20px;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            perspective: 1000px;
        }

        .feature-card {
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px 30px;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .feature-card:hover {
            transform: translateY(-10px) rotateX(2deg) rotateY(2deg);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .feature-icon-wrapper {
            margin-bottom: 24px;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) translateZ(20px);
        }

        .icon-svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        .feature-card h3 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover h3 {
            transform: translateZ(15px);
        }

        .feature-card p {
            font-size: 16px;
            line-height: 1.6;
            color: #6b7280;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover p {
            transform: translateZ(10px);
        }

        .feature-hover {
            position: absolute;
            inset: 0;
            border-radius: 24px;
            background: radial-gradient(circle at 50% 50%,
                    rgba(99, 102, 241, 0.1) 0%,
                    rgba(139, 92, 246, 0.05) 50%,
                    transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .feature-card:hover .feature-hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .features-section {
                padding: 80px 0;
            }

            .gradient-heading {
                font-size: 36px;
            }

            .section-subtext {
                font-size: 18px;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        /* Add a subtle animation to the cards on scroll */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .feature-card:nth-child(1) {
            animation-delay: 0.2s;
        }

        .feature-card:nth-child(2) {
            animation-delay: 0.4s;
        }

        .feature-card:nth-child(3) {
            animation-delay: 0.6s;
        }
    </style> -->
    <style>
        /* Features Section Styles */
        .features-section {
            position: relative;
            padding: 120px 0;
            background: linear-gradient(180deg, #ffffff 0%, #fff9f6 100%);
            overflow: hidden;
        }

        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(219, 89, 25, 0.2) 50%, transparent 100%);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .gradient-heading {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #db5919 0%, #ff8f59 50%, #ffb391 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
            position: relative;
            display: inline-block;
        }

        .gradient-heading::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #db5919, #ff8f59);
            border-radius: 2px;
        }

        .section-subtext {
            font-size: 20px;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            perspective: 1000px;
        }

        .feature-card {
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px 30px;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
            box-shadow: 0 4px 6px -1px rgba(219, 89, 25, 0.1),
                0 2px 4px -1px rgba(219, 89, 25, 0.06);
        }

        .feature-card:hover {
            transform: translateY(-10px) rotateX(2deg) rotateY(2deg);
            box-shadow: 0 20px 25px -5px rgba(219, 89, 25, 0.1),
                0 10px 10px -5px rgba(219, 89, 25, 0.04);
        }

        .feature-icon-wrapper {
            margin-bottom: 24px;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            background: linear-gradient(135deg, #db5919 0%, #ff8f59 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) translateZ(20px);
        }

        .icon-svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        .feature-card h3 {
            font-size: 24px;
            font-weight: 700;
            color: #db5919;
            margin-bottom: 16px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover h3 {
            transform: translateZ(15px);
        }

        .feature-card p {
            font-size: 16px;
            line-height: 1.6;
            color: #6b7280;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover p {
            transform: translateZ(10px);
        }

        .feature-hover {
            position: absolute;
            inset: 0;
            border-radius: 24px;
            background: radial-gradient(circle at 50% 50%,
                    rgba(219, 89, 25, 0.1) 0%,
                    rgba(255, 143, 89, 0.05) 50%,
                    transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .feature-card:hover .feature-hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .features-section {
                padding: 80px 0;
            }

            .gradient-heading {
                font-size: 36px;
            }

            .section-subtext {
                font-size: 18px;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        /* Add a subtle animation to the cards on scroll */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .feature-card:nth-child(1) {
            animation-delay: 0.2s;
        }

        .feature-card:nth-child(2) {
            animation-delay: 0.4s;
        }

        .feature-card:nth-child(3) {
            animation-delay: 0.6s;
        }
    </style>


    <!-- css for the ai tool  -->
    <!-- <style>
        .ai-section {
            padding: 100px 20px;
            position: relative;
            background-color: #0A0F1C;
        }

        /* .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
        } */

        /* Animated background */
        .animated-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }



        @keyframes bgMove {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Header styles */
        .section-header {
            text-align: center;
            position: relative;
            margin-bottom: 80px;
            z-index: 1;
        }

        .gradient-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, rgb(21, 76, 179), #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards;
        }

        .section-description {
            font-size: 1.2rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards 0.2s;
        }

        /* Tool items */
        .ai-tools {
            display: flex;
            flex-direction: column;
            gap: 120px;
            position: relative;
            z-index: 1;
        }

        .tool-item {
            display: flex;
            align-items: center;
            gap: 60px;
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s ease;
            padding: 40px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .tool-item.right {
            flex-direction: row-reverse;
            transform: translateX(50px);
        }

        .tool-item.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .tool-content {
            flex: 1;
        }

        .tool-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #6366f1, #0A0F1C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tool-description {
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .tool-features {
            list-style: none;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .feature-item::before {
            content: '•';
            margin-right: 12px;
            color: #6366f1;
            font-size: 1.5rem;
        }

        .tool-image {
            width: 400px;
            height: 300px;
            background: linear-gradient(135deg, #6366f1, #0A0F1C);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .tool-image:hover {
            transform: translateY(-10px);
        }

        .tool-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 2s infinite;
        }

        /* New floating shapes decoration */
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(236, 72, 153, 0.2));
            animation: float 6s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 5%;
            animation-delay: -2s;
        }

        .shape:nth-child(3) {
            width: 70px;
            height: 70px;
            top: 30%;
            right: 15%;
            animation-delay: -4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(-45deg);
            }

            100% {
                transform: translateX(100%) rotate(-45deg);
            }
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive styles */
        @media (max-width: 968px) {
            .gradient-title {
                font-size: 2.5rem;
            }

            .tool-item,
            .tool-item.right {
                flex-direction: column;
                text-align: center;
                gap: 40px;
                padding: 30px;
            }

            .tool-image {
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
            }

            .feature-item {
                justify-content: center;
            }

            .shape {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .gradient-title {
                font-size: 2rem;
            }

            .section-description {
                font-size: 1rem;
            }

            .tool-title {
                font-size: 1.8rem;
            }

            .tool-description {
                font-size: 1rem;
            }
        }
    </style> -->
    <style>
        .ai-section {
            padding: 100px 20px;
            position: relative;
            background-color: #0A0F1C;
        }

        /* Animated background */
        .animated-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        @keyframes bgMove {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Header styles */
        .section-header {
            text-align: center;
            position: relative;
            margin-bottom: 80px;
            z-index: 1;
        }

        .gradient-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #db5919, #ff8f59);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards;
        }

        .section-description {
            font-size: 1.2rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards 0.2s;
        }

        /* Tool items */
        .ai-tools {
            display: flex;
            flex-direction: column;
            gap: 120px;
            position: relative;
            z-index: 1;
        }

        .tool-item {
            display: flex;
            align-items: center;
            gap: 60px;
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s ease;
            padding: 40px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(219, 89, 25, 0.1);
        }

        .tool-item.right {
            flex-direction: row-reverse;
            transform: translateX(50px);
        }

        .tool-item.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .tool-content {
            flex: 1;
        }

        .tool-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #db5919, #ff8f59);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tool-description {
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .tool-features {
            list-style: none;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .feature-item::before {
            content: '•';
            margin-right: 12px;
            color: #db5919;
            font-size: 1.5rem;
        }

        .tool-image {
            width: 400px;
            height: 300px;
            background: linear-gradient(135deg, #db5919, #ff8f59);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .tool-image:hover {
            transform: translateY(-10px);
        }

        .tool-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 2s infinite;
        }

        /* Floating shapes decoration */
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(219, 89, 25, 0.2), rgba(255, 143, 89, 0.2));
            animation: float 6s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 5%;
            animation-delay: -2s;
        }

        .shape:nth-child(3) {
            width: 70px;
            height: 70px;
            top: 30%;
            right: 15%;
            animation-delay: -4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(-45deg);
            }

            100% {
                transform: translateX(100%) rotate(-45deg);
            }
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive styles */
        @media (max-width: 968px) {
            .gradient-title {
                font-size: 2.5rem;
            }

            .tool-item,
            .tool-item.right {
                flex-direction: column;
                text-align: center;
                gap: 40px;
                padding: 30px;
            }

            .tool-image {
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
            }

            .feature-item {
                justify-content: center;
            }

            .shape {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .gradient-title {
                font-size: 2rem;
            }

            .section-description {
                font-size: 1rem;
            }

            .tool-title {
                font-size: 1.8rem;
            }

            .tool-description {
                font-size: 1rem;
            }
        }
    </style>

    <style>
        .Business_Knobly_logo img {
            width: 30% !important;
        }
    </style>


</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="fixed w-full  bg-white/10 shadow-lg z-50 border-b border-white/20">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="Business_Knobly_logo">
                    <img src="inc/img/knobly_logo.png" alt="" w="30" h="auto">
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex space-x-8">
                    <a href="#features" class="text-cream text-lg font-medium hover:text-cyan-300 transition-colors duration-300">Features</a>
                    <a href="#editor" class="text-cream text-lg font-medium hover:text-cyan-300 transition-colors duration-300">Editor</a>
                    <a href="#ai" class="text-cream text-lg font-medium hover:text-cyan-300 transition-colors duration-300">Buddy</a>
                    <a href="#pricing" class="text-cream text-lg font-medium hover:text-cyan-300 transition-colors duration-300">Pricing</a>
                </div>

                <!-- CTA Button -->
                <div>
                    <button class="primary-button">
                        Get Started
                    </button>
                </div>
            </div>
        </div>
    </nav>


    <!-- Hero Section -->
    <section class="hero-section">
        <div class="noise-bg"></div>
        <div class="gradient-sphere sphere-1"></div>
        <div class="gradient-sphere sphere-2"></div>

        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <div class="hero-badge">
                        <svg class="badge-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="url(#paint0_linear)" stroke-width="2" />
                            <defs>
                                <linearGradient id="paint0_linear" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#60A5FA" />
                                    <stop offset="1" stop-color="#A855F7" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <span>Meet Your Growth Companion</span>
                    </div>

                    <h1 class="hero-title">
                        Your All-in-One <span class="gradient-text">Business Growth</span><br />
                        Platform
                    </h1>

                    <p class="hero-description">
                        Experience the future of content creation with KnoblyCream. From AI-powered writing assistance to custom newsletters, meet your personal writing buddy, and access powerful editing tools - all in one place.
                    </p>

                    <div class="cta-group">
                        <button class="primary-button">Get Started Free</button>
                        <button class="demo-button">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 6L15 12L9 18" stroke="white" stroke-width="2" stroke-linecap="round" />
                            </svg>
                            Explore Features
                        </button>
                    </div>

                    <div class="stats-row">
                        <div class="stat-item">
                            <span class="stat-value">20+</span>
                            <span class="stat-label">Smart Features</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">10+</span>
                            <span class="stat-label">AI Buddies</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">100%</span>
                            <span class="stat-label">Creative Freedom</span>
                        </div>
                    </div>
                </div>

                <div class="hero-image-wrapper">
                    <div class="image-grid"></div>
                    <img src="inc/img/platform-interface.png" alt="KnoblyCream Platform Interface" class="hero-image" />
                </div>
            </div>
        </div>
    </section>
    <!-- Editor Demo Section with Interactive Elements -->
    <section id="editor" class="py-20 bg-gradient-to-br from-indigo-50 to-cyan-50">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 pr-8" data-aos="fade-right">
                    <h2 class="text-4xl font-bold mb-6">
                        <span class="gradient-text">Powerful Editor</span>
                    </h2>
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        Create professional newsletters with our intuitive editor. Drag and drop elements, customize styles, and preview your work in real-time.
                    </p>
                    <div class="space-y-6">
                        <div class="flex items-center p-4 bg-white rounded-lg shadow-md transform hover:-translate-x-2 transition-all duration-300">
                            <div class="bg-gradient-to-r from-indigo-500 to-cyan-500 p-3 rounded-full mr-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold">Drag-and-Drop Interface</h4>
                                <p class="text-gray-600">Build your newsletter with simple drag and drop actions</p>
                            </div>
                        </div>
                        <!-- More feature items -->
                    </div>
                </div>
                <div class="md:w-1/2 mt-12 md:mt-0" data-aos="fade-left">
                    <img src="inc/img/newEditor.png" alt="Editor Interface" class="rounded-2xl shadow-2xl transform hover:scale-105 transition-all duration-500" />
                </div>
            </div>
        </div>
    </section>
    <section id="xpress" class="py-20 bg-gradient-to-br from-indigo-50 to-cyan-50">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center gap-10">
                <div class="md:w-1/2 mt-12 md:mt-0" data-aos="fade-left">
                    <img src="inc/img/xpressDashboard.png" alt="Xpress Dashboard" class="rounded-2xl shadow-2xl transform hover:scale-105 transition-all duration-500" />
                </div>
                <div class="md:w-1/2 pr-8" data-aos="fade-right">
                    <h2 class="text-4xl font-bold mb-6">
                        <span class="gradient-text">xPress: Instants Bulk Mailing</span>
                    </h2>
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        Send thousands of emails with just a single click. Xpress ensures high deliverability, quick scheduling, and real-time tracking to maximize efficiency.
                    </p>
                    <div class="space-y-6">
                        <div class="flex items-center p-4 bg-white rounded-lg shadow-md transform hover:-translate-x-2 transition-all duration-300">
                            <div class="bg-gradient-to-r from-indigo-500 to-cyan-500 p-3 rounded-full mr-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold">One-Click Bulk Mailing</h4>
                                <p class="text-gray-600">Send thousands of emails instantly with a single click.</p>
                            </div>
                        </div>
                        <div class="flex items-center p-4 bg-white rounded-lg shadow-md transform hover:-translate-x-2 transition-all duration-300">
                            <div class="bg-gradient-to-r from-indigo-500 to-cyan-500 p-3 rounded-full mr-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold">Real-Time Tracking</h4>
                                <p class="text-gray-600">Monitor delivery rates, opens, and clicks in real time.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="features-container">
            <div class="section-header">
                <h2 class="gradient-heading">Unleash Your Creativity</h2>
                <p class="section-subtext">Discover the tools that transform your writing experience</p>
            </div>

            <div class="features-grid">
                <!-- Feature 1: Write -->
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <div class="feature-icon">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3>Smart Writing</h3>
                    <p>Create compelling content with AI-powered assistance that adapts to your unique style.</p>
                    <div class="feature-hover"></div>
                </div>

                <!-- Feature 2: AI Buddy -->
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <div class="feature-icon">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                            </svg>
                        </div>
                    </div>
                    <h3>AI Buddies</h3>
                    <p>Choose from unique AI personalities that help brainstorm, edit, and enhance your writing journey.</p>
                    <div class="feature-hover"></div>
                </div>

                <!-- Feature 3: Curate -->
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <div class="feature-icon">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                    </div>
                    <h3>Content Curation</h3>
                    <p>Discover and organize inspiring content with our intelligent curation system.</p>
                    <div class="feature-hover"></div>
                </div>
            </div>
        </div>
    </section>

    
    
    <!-- AI Tools Section with Interactive Cards -->

    <section class="ai-section">
        <div class="animated-bg"></div>
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <div class="container">
            <div class="section-header">
                <h2 class="gradient-title">AI-Powered Creation</h2>
                <p class="section-description">Transform your content with our suite of advanced AI tools designed to enhance your creative workflow</p>
            </div>

            <div class="ai-tools">
                <!-- Tool Item 1 -->
                <div class="tool-item">
                    <div class="tool-content">
                        <h3 class="tool-title">AI Article Generation</h3>
                        <p class="tool-description">Create engaging, SEO-optimized content in minutes with advanced AI assistance that understands your brand voice and target audience.</p>
                        <ul class="tool-features">
                            <li class="feature-item">Smart Templates & AI Writing</li>
                            <li class="feature-item">Advanced SEO Optimization</li>
                            <li class="feature-item">Multi-language Support</li>
                        </ul>
                    </div>
                    <div class="tool-image">
                        <img src="inc/img/about-company-2.jpg" alt="">
                    </div>
                </div>

                <!-- Tool Item 2 -->
                <div class="tool-item right">
                    <div class="tool-content">
                        <h3 class="tool-title">Newsletter</h3>
                        <p class="tool-description">Automate your newsletter creation process with AI-powered content curation and personalization features.</p>
                        <ul class="tool-features">
                            <li class="feature-item">Intelligent Content Automation</li>
                            <li class="feature-item">Real-time Audience Analytics</li>
                            <!-- <li class="feature-item">Smart A/B Testing</li> -->
                        </ul>
                    </div>
                    <div class="tool-image">
                        <img src="inc/img/newletter.png" alt="">
                    </div>
                </div>

                <!-- Tool Item 3 -->
                <div class="tool-item">
                    <div class="tool-content">
                        <h3 class="tool-title">Creative Assistant</h3>
                        <p class="tool-description">Boost your creative workflow with AI-powered suggestions and automated asset management.</p>
                        <ul class="tool-features">
                            <li class="feature-item">Intelligent Design Suggestions</li>
                            <li class="feature-item">Advanced Style Generation</li>
                            <!-- <li class="feature-item">Smart Asset Management</li> -->
                        </ul>
                    </div>
                    <div class="tool-image">
                        <img src="inc/img/buudy.png" alt="">
                    </div>
                </div>
            </div>
        </div>
    </section>




    <section class="relative py-36 bg-gradient-to-br from-yellow-900 via-black to-gray-800 overflow-hidden">
        <!-- Background Animated Gradient with Floating Light Effects -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-purple-700/30 to-transparent animate-pulse"></div>

        <!-- Floating Particles & Light Flares -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-16 left-12 w-20 h-20 bg-white rounded-full opacity-30 blur-2xl animate-pulse"></div>
            <div class="absolute bottom-28 right-16 w-32 h-32 bg-purple-500 rounded-full opacity-20 blur-3xl animate-bounce"></div>
            <div class="absolute top-1/2 left-1/4 w-24 h-24 bg-pink-400 rounded-full opacity-40 blur-xl animate-fade"></div>
            <div class="absolute bottom-10 right-1/3 w-28 h-28 bg-indigo-400 rounded-full opacity-25 blur-xl animate-float"></div>
        </div>

        <div class="container mx-auto px-6 text-center relative z-10">
            <!-- Heading with Enhanced Neon Glow -->
            <h2 class="text-7xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-white to-pink-400 drop-shadow-[0_0_15px_rgba(255,255,255,0.8)] animate-fade-in" data-aos="zoom-in">
                Unleash Your Creativity
            </h2>

            <!-- Subheading with Enhanced Typography -->
            <p class="text-xl text-gray-300 mt-6 max-w-3xl mx-auto animate-fade-in leading-relaxed tracking-wide" data-aos="fade-up">
                Join a community of innovators and dreamers. Elevate your ideas with cutting-edge tools designed for modern creators.
            </p>

            <!-- Call-to-Action Button with 3D Effect -->
            <button class="mt-10 px-16 py-5 text-lg font-bold text-white bg-gradient-to-r from-pink-500 to-purple-400 rounded-full shadow-[0_5px_15px_rgba(255,100,255,0.5)] transform transition-all duration-500 hover:scale-110 hover:shadow-[0_10px_25px_rgba(255,100,255,0.8)] relative overflow-hidden group animate-fade-in" data-aos="flip-up">
                <span class="absolute inset-0 bg-gradient-to-r from-purple-500 to-pink-600 opacity-0 group-hover:opacity-50 transition-opacity duration-300"></span>
                <span class="relative z-10">Join for Free</span>
            </button>
        </div>
    </section>



    <!-- Modern Footer -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="container mx-auto px-6 flex flex-col md:flex-row items-center justify-between text-center md:text-left">
            <p class="text-gray-400">&copy; 2025 KnoblyCream. All rights reserved.</p>
            <div class="mt-4 md:mt-0 flex space-x-6">
                <a href="#" class="text-gray-400 hover:text-white transition">Privacy Policy</a>
                <a href="#" class="text-gray-400 hover:text-white transition">Terms of Service</a>
                <a href="#" class="text-gray-400 hover:text-white transition">Contact Us</a>
            </div>
        </div>
    </footer>



    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });

        // GSAP Animations
        gsap.from(".gradient-text", {
            duration: 1,
            y: 30,
            opacity: 0,
            stagger: 0.2,
            ease: "power3.out"
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Parallax effect on scroll
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.floating');
            parallaxElements.forEach(element => {
                const speed = 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    </script>

    <!-- this one for hero section  -->
    <script>
        // Parallax effect
        document.addEventListener('mousemove', (e) => {
            const mouseX = e.clientX / window.innerWidth - 0.5;
            const mouseY = e.clientY / window.innerHeight - 0.5;

            const heroImage = document.querySelector('.hero-image');
            const imageGrid = document.querySelector('.image-grid');
            const spheres = document.querySelectorAll('.gradient-sphere');

            heroImage.style.transform = `
                perspective(1000px)
                rotateY(${mouseX * 5}deg)
                rotateX(${-mouseY * 5}deg)
                translateZ(20px)
            `;

            imageGrid.style.transform = `
                perspective(1000px)
                rotateX(45deg)
                scale(0.9)
                translateX(${mouseX * 20}px)
                translateY(${mouseY * 20}px)
            `;

            spheres.forEach((sphere, index) => {
                sphere.style.transform = `
                    translate(
                        ${mouseX * (index + 1) * 30}px,
                        ${mouseY * (index + 1) * 30}px
                    )
                `;
            });
        });

        // Smooth scroll for buttons
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('mouseenter', (e) => {
                button.style.transform = 'translateY(-2px)';
            });

            button.addEventListener('mouseleave', (e) => {
                button.style.transform = 'translateY(0)';
            });
        });
    </script>

    <!-- script for the ai tool  -->
    <script>
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.2,
            rootMargin: '0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all tool items
        document.querySelectorAll('.tool-item').forEach(item => {
            observer.observe(item);
        });

        // Mouse parallax effect
        document.addEventListener('mousemove', (e) => {
            const tools = document.querySelectorAll('.tool-image');
            const shapes = document.querySelectorAll('.shape');

            const mouseX = e.clientX / window.innerWidth - 0.5;
            const mouseY = e.clientY / window.innerHeight - 0.5;

            tools.forEach(tool => {
                const rect = tool.getBoundingClientRect();
                const toolCenterX = rect.left + rect.width / 2;
                const toolCenterY = rect.top + rect.height / 2;

                const moveX = (e.clientX - toolCenterX) * 0.01;
                const moveY = (e.clientY - toolCenterY) * 0.01;

                tool.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });

            shapes.forEach(shape => {
                const moveX = mouseX * 20;
                const moveY = mouseY * 20;
                shape.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });
        });
    </script>
</body>

</html>