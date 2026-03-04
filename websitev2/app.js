// Render Blog Posts
(function() {
    const container = document.getElementById('blogContainer');
    if (typeof blogPosts !== 'undefined' && container) {
        container.innerHTML = blogPosts.map(post => `
            <article class="blog-post" id="post-${post.id}">
                <div class="blog-post-header">
                    <div class="blog-post-meta">${post.date} &mdash; ${post.tag}</div>
                    <h2 class="blog-post-title">${post.title}</h2>
                </div>
                <div class="blog-post-body">${post.body}</div>
            </article>
        `).join('');
    }
})();

// Page Navigation
function showPage(pageId) {
    // Hide all pages
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });

    // Show selected page
    document.getElementById(pageId).classList.add('active');

    // Update nav links
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.classList.remove('active');
        if (link.dataset.page === pageId) {
            link.classList.add('active');
        }
    });

    // Close mobile menu
    document.querySelector('.nav-links').classList.remove('active');

    // Scroll to top
    window.scrollTo(0, 0);
}

// Mobile Menu Toggle
function toggleMenu() {
    document.querySelector('.nav-links').classList.toggle('active');
}

// Accordion Toggle (for Impact page)
function toggleAccordion(header) {
    const content = header.nextElementSibling;
    const icon = header.querySelector('.accordion-icon');

    // Toggle active class
    content.classList.toggle('active');

    // Update icon
    if (content.classList.contains('active')) {
        icon.textContent = '−';
    } else {
        icon.textContent = '+';
    }
}

// Impact 2 Tab Switching
function switchImpact2Tab(tabId) {
    // Close any open popups
    document.querySelectorAll('#impact-examples .framework-popup.active').forEach(function(p) {
        p.classList.remove('active');
    });

    // Hide all panels, show selected
    document.querySelectorAll('.impact2-panel').forEach(function(panel) {
        panel.classList.remove('active');
    });
    document.getElementById('impact2-' + tabId).classList.add('active');

    // Update tab active state
    document.querySelectorAll('.impact2-tab').forEach(function(tab) {
        tab.classList.remove('active');
    });
    document.querySelector('.impact2-tab[onclick="switchImpact2Tab(\'' + tabId + '\')"]').classList.add('active');
}

// Framework Popup Toggle
function toggleFrameworkPopup(element) {
    const popup = element.nextElementSibling;
    const isActive = popup.classList.contains('active');

    // Close all open popups first
    document.querySelectorAll('.framework-popup.active').forEach(function(p) {
        p.classList.remove('active');
    });

    // Toggle the clicked one (unless it was already open)
    if (!isActive) {
        popup.classList.add('active');
    }
}

// Close framework popups when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.framework-cell')) {
        document.querySelectorAll('.framework-popup.active').forEach(function(p) {
            p.classList.remove('active');
        });
    }
});

// Floating Chat Widget
(function() {
    const bubble = document.getElementById('chatBubble');
    const panel = document.getElementById('chatPanel');
    const closeBtn = document.getElementById('chatClose');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    const messages = document.getElementById('chatMessages');
    let opened = false;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function addMessage(text, type) {
        const msg = document.createElement('div');
        msg.className = 'chat-msg ' + type;
        msg.innerHTML = '<p>' + text + '</p>';
        messages.appendChild(msg);
        messages.scrollTop = messages.scrollHeight;
        return msg;
    }

    function toggleChat() {
        const isOpen = panel.classList.toggle('open');
        if (isOpen && !opened) {
            opened = true;
            addMessage("Hi \u2014 I'm an AI-based representation of Paul Willmott (not the real Paul). I can help you think through digital strategy, AI value creation, and transformation choices like a board-level sparring partner. What are you working on, and what decision do you need to make?", 'bot');
        }
        if (isOpen) input.focus();
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        addMessage(escapeHtml(text), 'user');
        input.value = '';

        const thinking = addMessage('<em>Thinking...</em>', 'bot');
        input.disabled = true;
        sendBtn.disabled = true;

        try {
            const res = await fetch('chat-api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();
            messages.removeChild(thinking);
            addMessage(data.response || data.error || 'Sorry, something went wrong.', 'bot');
        } catch {
            messages.removeChild(thinking);
            addMessage('Sorry, I couldn\'t connect to the server. Please try again later.', 'bot');
        } finally {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        }
    }

    bubble.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });
})();

// Blueprint Animation - Fade In & Slide
const blueprintObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, {
    threshold: 0.2,
    rootMargin: '0px 0px -100px 0px'
});

// Observe all principles for blueprint animation
document.querySelectorAll('.principle').forEach(principle => {
    blueprintObserver.observe(principle);
});

// Blueprint Line Drawing Animation
const blueprintLine = document.querySelector('.blueprint-line');
if (blueprintLine) {
    window.addEventListener('scroll', () => {
        const blueprintSection = document.querySelector('.blueprint-section');
        if (blueprintSection) {
            const rect = blueprintSection.getBoundingClientRect();
            const scrollProgress = Math.max(0, Math.min(1, (window.innerHeight - rect.top) / rect.height));
            blueprintLine.style.clipPath = `inset(${(1 - scrollProgress) * 100}% 0 0 0)`;
        }
    });
}

// Add scroll animation for timeline items
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe timeline items when About page is shown
document.querySelectorAll('.timeline-item').forEach(item => {
    observer.observe(item);
});

