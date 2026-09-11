
// Template definitions
const templates = {
    template1: {
        name: 'Modern Elegance',
        html: `
        <div class="draggable" data-type="section" style="top: 0; left: 0; width: 100%; background: linear-gradient(45deg, #6EE7B7, #3B82F6); padding: 60px 20px;">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <h1 contenteditable="true" style="color: white; font-size: 50px; font-family: 'Roboto', sans-serif; font-weight: 700; margin: 0;">LUXURY LIVING</h1>
        <p contenteditable="true" style="color: #E0F7FA; font-size: 20px; margin-top: 15px; font-style: italic; letter-spacing: 1px;">Elegance in Every Detail</p>
    </div>
</div>

<div class="draggable" data-type="section" style="top: 250px; left: 0; width: 100%; background-color: #F3F4F6; padding: 50px 20px;">
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 12px 30px rgba(0,0,0,0.1);">
                <h2 contenteditable="true" style="color: #1D4ED8; font-size: 32px; margin: 0; font-family: 'Roboto', sans-serif;">Exclusive Luxury Homes</h2>
                <div style="width: 60px; height: 4px; background-color: #3B82F6; margin: 20px 0;"></div>
                <p contenteditable="true" style="color: #2D3748; line-height: 1.7; font-size: 18px;">
                    Discover a collection of the finest luxury homes with unparalleled designs. Indulge in the comfort of modern living with spacious interiors, world-class amenities, and stunning views.
                </p>
                
            </div>
            <div>
                <div style="background-color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 12px 30px rgba(0,0,0,0.1);">
                    <h3 contenteditable="true" style="color: #1D4ED8; font-size: 26px; margin: 0;">Key Features</h3>
                    <ul style="color: #4A5568; margin: 20px 0; padding-left: 20px; line-height: 1.8;">
                        <li contenteditable="true">Spacious Living Areas</li>
                        <li contenteditable="true">Private Pools and Gardens</li>
                        <li contenteditable="true">Smart Home Technology</li>
                    </ul>
                </div>
                <div style="text-align: center; padding: 30px; background-color: #E0F7FA; border-radius: 12px;">
                    <span contenteditable="true" style="color: #1D4ED8; font-weight: bold;">JOIN OUR EXCLUSIVE NETWORK</span>
                    <p contenteditable="true" style="color: #2D3748; margin: 10px 0;">Stay updated on the latest luxury properties</p>
                    <button contenteditable="true" style="background-color: #1D4ED8; color: white; border: none; padding: 12px 30px; border-radius: 30px; cursor: pointer; font-size: 18px; transition: background-color 0.3s;">
                        Subscribe Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

        `
    },

    template8: {
        name: 'Basic Newsletter',
        html: `
    <div class="draggable template-header" data-type="section" style="top: 0; left: 0; width: 100%; background-color: #db5919; padding: 20px; text-align: center;">
        <h1 contenteditable="true" style="color: #ffffff; margin: 0;">Your Newsletter Title</h1>
        <p contenteditable="true" style="color: #ffffff; margin-top: 10px;">Monthly Newsletter - January 2025</p>
    </div>

    <div class="draggable" data-type="section" style="top: 150px; left: 0; width: 100%; padding: 20px;">
        <h2 contenteditable="true" style="color: #db5919;">Featured Article</h2>
        <div class="draggable" data-type="image" style="width: 100%; max-width: 600px; margin: 20px 0;">
            <img src="/api/placeholder/600/300" alt="Featured Image" style="width: 100%; height: auto;">
        </div>
        <p contenteditable="true" style="color: #333333; line-height: 1.6;">
            Your main article content goes here. Click to edit this text and make it your own. You can discuss your latest news, updates, or any important announcements.
        </p>
    </div>

    <div class="draggable" data-type="section" style="top: 600px; left: 0; width: 100%; display: flex; gap: 20px; padding: 20px;">
        <div style="flex: 1;">
            <h3 contenteditable="true" style="color: #db5919;">Section One</h3>
            <p contenteditable="true" style="color: #333333; line-height: 1.6;">
                Edit this section to add your content. Perfect for short updates or highlights.
            </p>
        </div>
        <div style="flex: 1;">
            <h3 contenteditable="true" style="color: #db5919;">Section Two</h3>
            <p contenteditable="true" style="color: #333333; line-height: 1.6;">
                Another section for your content. You can add news, events, or any other information.
            </p>
        </div>
    </div>

    <div class="draggable" data-type="section" style="top: 800px; left: 0; width: 100%; background-color: #f5f5f5; padding: 20px; text-align: center;">
        <p contenteditable="true" style="color: #666666;">
            Contact us: <span contenteditable="true">your@email.com</span><br>
            <span contenteditable="true">Your Company Name</span> © 2025
        </p>
    </div>
`
    },
    template2: {
        name: 'Modern Layout',
        html: `
    <div class="draggable" data-type="section" style="top: 0; left: 0; width: 100%; background-color: #ffffff; border-bottom: 3px solid #db5919; padding: 20px;">
        <h1 contenteditable="true" style="color: #db5919; text-align: center; margin: 0;">Modern Newsletter</h1>
    </div>

    <div class="draggable" data-type="section" style="top: 100px; left: 0; width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding: 20px;">
        <div class="draggable" data-type="image" style="width: 100%;">
            <img src="/api/placeholder/500/400" alt="Main Image" style="width: 100%; height: auto;">
        </div>
        <div>
            <h2 contenteditable="true" style="color: #db5919;">Main Story</h2>
            <p contenteditable="true" style="color: #333333; line-height: 1.8;">
                Your featured story begins here. Make it engaging and informative. This modern layout emphasizes visual content alongside text.
            </p>
            <button contenteditable="true" style="background-color: #db5919; color: white; border: none; padding: 10px 20px; margin-top: 20px; cursor: pointer;">
                Read More
            </button>
        </div>
    </div>

    <div class="draggable" data-type="section" style="top: 550px; left: 0; width: 100%; background-color: #f8f8f8; padding: 30px; margin-top: 40px;">
        <h3 contenteditable="true" style="color: #db5919; text-align: center;">Latest Updates</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div>
                <h4 contenteditable="true" style="color: #db5919;">Update 1</h4>
                <p contenteditable="true" style="color: #333333;">Edit this content to add your latest news or updates.</p>
            </div>
            <div>
                <h4 contenteditable="true" style="color: #db5919;">Update 2</h4>
                <p contenteditable="true" style="color: #333333;">Another section for your important announcements.</p>
            </div>
            <div>
                <h4 contenteditable="true" style="color: #db5919;">Update 3</h4>
                <p contenteditable="true" style="color: #333333;">One more space for your valuable content.</p>
            </div>
        </div>
    </div>
`
    },
    template3: {
        name: 'Professional',
        html: `
    <div class="draggable" data-type="section" style="top: 0; left: 0; width: 100%; background: linear-gradient(to right, #db5919, #ff8a50); padding: 40px 20px;">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h1 contenteditable="true" style="color: white; margin: 0; font-size: 32px;">Professional Newsletter</h1>
            <p contenteditable="true" style="color: white; margin-top: 10px;">Industry Insights & Updates</p>
        </div>
    </div>

    <div class="draggable" data-type="section" style="top: 200px; left: 0; width: 100%; padding: 20px;">
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="display: flex; gap: 30px; margin-bottom: 40px;">
                <div style="flex: 2;">
                    <h2 contenteditable="true" style="color: #db5919;">Executive Summary</h2>
                    <p contenteditable="true" style="color: #333333; line-height: 1.8;">
                        Your professional content starts here. Perfect for business updates, industry news, and expert insights.
                    </p>
                </div>
                <div class="draggable" data-type="image" style="flex: 1;">
                    <img src="/api/placeholder/300/200" alt="Executive Image" style="width: 100%; height: auto;">
                </div>
            </div>

            <div style="background-color: #f9f9f9; padding: 20px; border-left: 4px solid #db5919; margin: 20px 0;">
                <h3 contenteditable="true" style="color: #db5919; margin-top: 0;">Key Highlights</h3>
                <ul style="color: #333333; line-height: 1.6;">
                    <li contenteditable="true">Edit this point to highlight key information</li>
                    <li contenteditable="true">Add another important point here</li>
                    <li contenteditable="true">One more key highlight for your readers</li>
                </ul>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 40px;">
                <div style="background-color: white; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h4 contenteditable="true" style="color: #db5919;">Industry Trends</h4>
                    <p contenteditable="true" style="color: #333333;">
                        Discuss current trends and developments in your industry.
                    </p>
                </div>
                <div style="background-color: white; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h4 contenteditable="true" style="color: #db5919;">Upcoming Events</h4>
                    <p contenteditable="true" style="color: #333333;">
                        Share information about upcoming events or important dates.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="draggable" data-type="section" style="top: 800px; left: 0; width: 100%; background-color: #333333; padding: 20px; text-align: center;">
        <p contenteditable="true" style="color: white; margin: 0;">
            Contact: <span contenteditable="true">your@company.com</span> | <span contenteditable="true">+1 (555) 123-4567</span>
        </p>
        <p contenteditable="true" style="color: white; margin: 5px 0;">
            <span contenteditable="true">Your Company Name</span> © 2025 All Rights Reserved
        </p>
    </div>
`
    },
    template4: {
        name: 'Gradient Modern',
        html: `
    <div class="draggable" data-type="section" style="top: 0; left: 0; width: 100%; background: linear-gradient(135deg, #6366F1, #A855F7); padding: 60px 20px;">
        <div style="max-width: 800px; margin: 0 auto; text-align: start;">
            <h1 contenteditable="true" style="color: white; font-size: 48px; margin: 0; font-weight: 800;">CREATIVE DIGEST</h1>
            <p contenteditable="true" style="color: rgba(255,255,255,0.9); font-size: 18px; margin-top: 15px;">Your Weekly Source of Inspiration</p>
        </div>
    </div>

    <div class="draggable" data-type="section" style="top: 200px; left: 0; width: 100%; padding: 40px 20px; background-color: #ffffff;">
        <div style="max-width: 800px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
            <div class="draggable" data-type="image" style="position: relative; overflow: hidden; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <img src="/api/placeholder/600/800" alt="Featured" style="width: 100%; height: auto;">
            </div>
            <div>
                <span contenteditable="true" style="background: linear-gradient(135deg, #6366F1, #A855F7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold;">FEATURED STORY</span>
                <h2 contenteditable="true" style="color: #1F2937; font-size: 32px; margin: 20px 0;">Design Trends 2025</h2>
                <p contenteditable="true" style="color: #4B5563; line-height: 1.8; font-size: 16px;">
                    Explore the latest trends shaping the future of design. From minimalist interfaces to bold typography, we cover it all.
                </p>
                <button contenteditable="true" style="background: linear-gradient(135deg, #6366F1, #A855F7); color: white; border: none; padding: 15px 30px; border-radius: 30px; font-weight: bold; margin-top: 20px; cursor: pointer;">
                    Read More →
                </button>
            </div>
        </div>
    </div>
`
    },
    template5: {
        name: 'Minimal Elegance',
        html: `
    <div class="draggable" data-type="section" style="top: 0; left: 0; width: 100%; background-color: #0F172A; padding: 40px 20px;">
        <div style="max-width: 800px; margin: 0 auto; text-align: start;">
            <h1 contenteditable="true" style="color: #E2E8F0; font-size: 36px; margin: 0; font-weight: 300; letter-spacing: 4px;">MINIMALIST</h1>
            <div style="width: 40px; height: 2px; background-color: #22D3EE; margin: 20px auto;"></div>
            <p contenteditable="true" style="color: #94A3B8; margin-top: 10px; font-size: 16px;">Clean • Simple • Effective</p>
        </div>
    </div>

    <div class="draggable" data-type="section" style="top: 200px; left: 0; width: 100%; padding: 60px 20px; background-color: #ffffff;">
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                <div style="text-align: center;">
                    <div class="draggable" data-type="image" style="width: 100%; aspect-ratio: 1; border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
                        <img src="/api/placeholder/400/400" alt="Design" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 contenteditable="true" style="color: #0F172A; font-size: 20px; margin: 0;">Design</h3>
                    <p contenteditable="true" style="color: #64748B; margin-top: 10px; font-size: 14px;">Minimalist approach to modern design solutions</p>
                </div>
                <div style="text-align: center;">
                    <div class="draggable" data-type="image" style="width: 100%; aspect-ratio: 1; border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
                        <img src="/api/placeholder/400/400" alt="Create" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 contenteditable="true" style="color: #0F172A; font-size: 20px; margin: 0;">Create</h3>
                    <p contenteditable="true" style="color: #64748B; margin-top: 10px; font-size: 14px;">Craft meaningful experiences through simplicity</p>
                </div>
                <div style="text-align: center;">
                    <div class="draggable" data-type="image" style="width: 100%; aspect-ratio: 1; border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
                        <img src="/api/placeholder/400/400" alt="Inspire" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 contenteditable="true" style="color: #0F172A; font-size: 20px; margin: 0;">Inspire</h3>
                    <p contenteditable="true" style="color: #64748B; margin-top: 10px; font-size: 14px;">Inspire others through innovative thinking</p>
                </div>
            </div>
        </div>
    </div>
`
    },
    template6: {
        name: 'Bold & Vibrant',
        html: `
    <div class="draggable" data-type="section" style="top: 0; left: 0; width: 100%; background-color: #FF0080; padding: 80px 20px;">
        <div style="max-width: 800px; margin: 0 auto; text-align: center; position: relative;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url('/api/placeholder/800/400') center/cover; opacity: 0.1;"></div>
            <h1 contenteditable="true" style="color: white; font-size: 56px; margin: 0; font-weight: 900; position: relative;">TRENDING NOW</h1>
            <p contenteditable="true" style="color: rgba(255,255,255,0.9); font-size: 20px; margin-top: 20px; position: relative;">The Latest in Fashion, Art & Culture</p>
        </div>
    </div>

    <div class="draggable" data-type="section" style="top: 300px; left: 0; width: 100%; padding: 60px 20px; background-color: #ffffff;">
        <div style="max-width: 1000px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px;">
                <div style="background-color: #F4F4F5; padding: 30px; border-radius: 20px;">
                    <span contenteditable="true" style="background-color: #FF0080; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;">FASHION</span>
                    <h2 contenteditable="true" style="color: #18181B; font-size: 28px; margin: 20px 0;">Summer Collection 2025</h2>
                    <p contenteditable="true" style="color: #71717A; line-height: 1.7;">
                        Discover the hottest trends for the upcoming season. Bold colors, unique patterns, and sustainable materials lead the way.
                    </p>
                    <div class="draggable" data-type="image" style="margin-top: 20px; border-radius: 10px; overflow: hidden;">
                        <img src="/api/placeholder/500/300" alt="Fashion" style="width: 100%; height: auto;">
                    </div>
                </div>
                <div style="background-color: #F4F4F5; padding: 30px; border-radius: 20px;">
                    <span contenteditable="true" style="background-color: #FF0080; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;">ART</span>
                    <h2 contenteditable="true" style="color: #18181B; font-size: 28px; margin: 20px 0;">Digital Art Revolution</h2>
                    <p contenteditable="true" style="color: #71717A; line-height: 1.7;">
                        Explore how digital artists are pushing boundaries and creating new forms of expression in the modern age.
                    </p>
                    <div class="draggable" data-type="image" style="margin-top: 20px; border-radius: 10px; overflow: hidden;">
                        <img src="/api/placeholder/500/300" alt="Art" style="width: 100%; height: auto;">
                    </div>
                </div>
            </div>
        </div>
    </div>
`
    },
    template7: {
        name: 'Nature Inspired',
        html: `
    <div class="draggable" data-type="section" style="top: 0; left: 0; width: 100%; background: linear-gradient(135deg, #059669, #10B981); padding: 50px 20px;">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h1 contenteditable="true" style="color: white; font-size: 42px; margin: 0; font-family: 'Georgia', serif;">ECO LIFESTYLE</h1>
            <p contenteditable="true" style="color: #D1FAE5; font-size: 18px; margin-top: 15px; font-style: italic;">Sustainable Living & Natural Wellness</p>
        </div>
    </div>

    <div class="draggable" data-type="section" style="top: 200px; left: 0; width: 100%; padding: 40px 20px; background-color: #F0FDF4;">
        <div style="max-width: 900px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
                <div style="background-color: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                    <h2 contenteditable="true" style="color: #059669; font-size: 28px; margin: 0;">Sustainable Living Guide</h2>
                    <div style="width: 50px; height: 3px; background-color: #10B981; margin: 20px 0;"></div>
                    <p contenteditable="true" style="color: #374151; line-height: 1.8; font-size: 16px;">
                        Discover practical tips and insights for living a more sustainable lifestyle. From reducing waste to energy conservation, learn how small changes can make a big impact.
                    </p>
                    <div class="draggable" data-type="image" style="margin: 20px 0; border-radius: 10px; overflow: hidden;">
                        <img src="/api/placeholder/600/300" alt="Sustainable Living" style="width: 100%; height: auto;">
                    </div>
                </div>
                <div>
                    <div style="background-color: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                        <h3 contenteditable="true" style="color: #059669; font-size: 20px; margin: 0;">Quick Tips</h3>
                        <ul style="color: #374151; margin: 15px 0; padding-left: 20px; line-height: 1.6;">
                            <li contenteditable="true">Reduce plastic usage</li>
                            <li contenteditable="true">Start composting</li>
                            <li contenteditable="true">Use eco-friendly products</li>
                        </ul>
                    </div>
                    <div style="text-align: center; padding: 20px; background-color: #D1FAE5; border-radius: 15px;">
                        <span contenteditable="true" style="color: #059669; font-weight: bold;">JOIN OUR COMMUNITY</span>
                        <p contenteditable="true" style="color: #374151; margin: 10px 0;">Get weekly tips and updates</p>
                        <button contenteditable="true" style="background-color: #059669; color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer;">
                            Subscribe Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
`
    }
};

class NewsletterEditor {
    constructor() {
        this.selectedElement = null;
        this.isDragging = false;
        this.currentTemplate = null;
        this.init();
    }

    init() {
        this.initializeTemplateSelection();
        this.initializeDragAndDrop();
        this.initializeToolbar();
        this.initializeImageUpload();
        this.initializeCanvasListeners();
    }

    initializeTemplateSelection() {
        const templateElements = document.querySelectorAll('.template');
        templateElements.forEach(template => {
            template.addEventListener('click', () => {
                this.loadTemplate(template.dataset.template);
            });
        });
    }
    loadTemplate(templateId) {
        const canvasArea = document.getElementById('canvasArea');
        this.currentTemplate = templateId;

        // Check if the templateId exists in templates
        if (templates[templateId] && templates[templateId].html) {
            // If it exists, set the innerHTML of the canvasArea
            canvasArea.innerHTML = templates[templateId].html;
            this.initializeDraggableElements();
        } else {
            // If the template is not found or doesn't have html, log an error or handle it
            console.error(`Template with ID '${templateId}' not found or doesn't have 'html' property.`);
        }
    }


    initializeDragAndDrop() {
        const elements = document.querySelectorAll('.element');
        elements.forEach(element => {
            element.addEventListener('dragstart', (e) => this.handleDragStart(e));
        });

        const canvasArea = document.getElementById('canvasArea');
        canvasArea.addEventListener('dragover', (e) => this.handleDragOver(e));
        canvasArea.addEventListener('drop', (e) => this.handleDrop(e));
    }

    handleDragStart(e) {
        e.dataTransfer.setData('text/plain', e.target.dataset.type);
    }

    handleDragOver(e) {
        e.preventDefault();
    }

    handleDrop(e) {
        e.preventDefault();
        const type = e.dataTransfer.getData('text/plain');
        const element = this.createNewElement(type);

        const canvasRect = e.target.getBoundingClientRect();
        const x = e.clientX - canvasRect.left;
        const y = e.clientY - canvasRect.top;

        element.style.left = `${x}px`;
        element.style.top = `${y}px`;

        document.getElementById('canvasArea').appendChild(element);
        this.initializeDraggableElements();
    }

    createNewElement(type) {
        const element = document.createElement('div');
        element.className = 'draggable';
        element.dataset.type = type;

        switch (type) {
            case 'text':
                element.innerHTML = '<p contenteditable="true">Double click to edit text</p>';
                break;
            case 'image':
                element.innerHTML = '<img src="newletter.png" alt="Placeholder">';
                break;
            case 'button':
                element.innerHTML = '<button contenteditable="true">Click Me</button>';
                break;
            case 'spacer':
                element.style.height = '20px';
                element.style.width = '100%';
                element.style.backgroundColor = '#f0f0f0';
                break;
        }

        return element;
    }

    initializeDraggableElements() {
        const draggables = document.querySelectorAll('.draggable');

        draggables.forEach(draggable => {
            interact(draggable)
                .draggable({
                    inertia: true,
                    modifiers: [
                        interact.modifiers.restrictRect({
                            restriction: 'parent',
                            endOnly: true
                        })
                    ],
                    autoScroll: true,
                    listeners: {
                        move: this.dragMoveListener.bind(this)
                    }
                })
                .resizable({
                    edges: { left: true, right: true, bottom: true, top: true },
                    restrictEdges: {
                        outer: 'parent',
                        endOnly: true
                    },
                    listeners: {
                        move: this.resizeMoveListener.bind(this)
                    }
                });

            draggable.addEventListener('click', (e) => {
                this.handleElementSelect(draggable, e);
            });
        });
    }

    dragMoveListener(event) {
        const target = event.target;
        const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
        const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

        target.style.transform = `translate(${x}px, ${y}px)`;
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
    }

    resizeMoveListener(event) {
        const target = event.target;
        let x = parseFloat(target.getAttribute('data-x')) || 0;
        let y = parseFloat(target.getAttribute('data-y')) || 0;

        target.style.width = `${event.rect.width}px`;
        target.style.height = `${event.rect.height}px`;

        x += event.deltaRect.left;
        y += event.deltaRect.top;

        target.style.transform = `translate(${x}px, ${y}px)`;
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
    }

    handleElementSelect(element, event) {
        // Deselect previous element
        if (this.selectedElement) {
            this.selectedElement.classList.remove('selected');
        }

        // Select new element
        this.selectedElement = element;
        element.classList.add('selected');

        // Update toolbar to reflect selected element's properties
        this.updateToolbarState();

        event.stopPropagation();
    }

    initializeToolbar() {
        const toolbar = document.querySelector('.toolbar');

        // Font controls
        toolbar.querySelector('#fontFamily').addEventListener('change', (e) => {
            if (this.selectedElement) {
                this.selectedElement.style.fontFamily = e.target.value;
            }
        });

        toolbar.querySelector('#fontSize').addEventListener('change', (e) => {
            if (this.selectedElement) {
                this.selectedElement.style.fontSize = `${e.target.value}px`;
            }
        });

        // Style controls
        toolbar.querySelector('#boldText').addEventListener('click', () => {
            if (this.selectedElement) {
                this.selectedElement.style.fontWeight =
                    this.selectedElement.style.fontWeight === 'bold' ? 'normal' : 'bold';
            }
        });

        toolbar.querySelector('#italicText').addEventListener('click', () => {
            if (this.selectedElement) {
                this.selectedElement.style.fontStyle =
                    this.selectedElement.style.fontStyle === 'italic' ? 'normal' : 'italic';
            }
        });

        toolbar.querySelector('#textColor').addEventListener('input', (e) => {
            if (this.selectedElement) {
                this.selectedElement.style.color = e.target.value;
            }
        });

        // Save and export
        toolbar.querySelector('#saveTemplate').addEventListener('click', () => this.saveTemplate());
        toolbar.querySelector('#exportTemplate').addEventListener('click', () => this.exportTemplate());
    }

    updateToolbarState() {
        if (!this.selectedElement) return;

        const computedStyle = window.getComputedStyle(this.selectedElement);

        // Update font controls
        document.querySelector('#fontFamily').value = computedStyle.fontFamily.split(',')[0].replace(/['"]/g, '');
        document.querySelector('#fontSize').value = parseInt(computedStyle.fontSize);
        document.querySelector('#textColor').value = this.rgbToHex(computedStyle.color);
    }

    rgbToHex(rgb) {
        // Convert RGB to HEX color
        const rgbValues = rgb.match(/\d+/g);
        return `#${rgbValues.map(x => parseInt(x).toString(16).padStart(2, '0')).join('')}`;
    }

    initializeImageUpload() {
        const imageUpload = document.getElementById('imageUpload');
        imageUpload.addEventListener('change', (e) => this.handleImageUpload(e));
    }

    handleImageUpload(e) {
        const file = e.target.files[0];
        if (file && this.selectedElement) {
            const reader = new FileReader();
            reader.onload = (event) => {
                const img = this.selectedElement.querySelector('img');
                if (img) {
                    img.src = event.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
    }

    initializeCanvasListeners() {
        const canvasArea = document.getElementById('canvasArea');
        canvasArea.addEventListener('click', (e) => {
            if (e.target === canvasArea) {
                if (this.selectedElement) {
                    this.selectedElement.classList.remove('selected');
                    this.selectedElement = null;
                }
            }
        });
    }

    saveTemplate() {
        const canvasArea = document.getElementById('canvasArea');
        const templateData = {
            html: canvasArea.innerHTML,
            name: `Template_${Date.now()}`
        };

        // Send to backend
        fetch('save_template.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(templateData)
        })
            .then(response => response.json())
            .then(data => {
                alert('Template saved successfully!');
            })
            .catch(error => {
                console.error('Error saving template:', error);
                alert('Error saving template');
            });
    }

    exportTemplate() {
        const canvasArea = document.getElementById('canvasArea');
        const content = canvasArea.innerHTML;

        // Basic HTML wrapper for the newsletter
        const fullHTML = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Newsletter</title>
        <style>
            .draggable { position: absolute; }
        </style>
    </head>
    <body>
        <div class="newsletter-content">
            ${content}
        </div>
    </body>
    </html>
`;

        // Create a blob and download
        const blob = new Blob([fullHTML], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'newsletter.html';
        a.click();
        window.URL.revokeObjectURL(url);
    }
}

// Initialize the editor when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.newsletterEditor = new NewsletterEditor();
});

function initializeColorControls() {
    const colorPicker = document.getElementById('sectionColor');
    colorPicker.addEventListener('input', (e) => {
        if (this.selectedElement) {
            this.selectedElement.style.backgroundColor = e.target.value;
        }
    });
}


















