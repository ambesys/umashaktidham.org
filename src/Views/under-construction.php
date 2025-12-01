<?php
// Under Construction Page Template
$pageTitle = $pageTitle ?? 'Page Under Construction';
?>

<div class="under-construction-page">
    <div class="container">
        <div class="construction-content">
            <div class="construction-icon">
                <i class="fas fa-tools"></i>
            </div>

            <!-- page title is expected to be provided by the including view via the .page-heading hero -->
            <div class="construction-message">
                <h2>Page Under Construction</h2>
                <p>We're working hard to bring you this content. Please check back soon!</p>
            </div>

            <div class="construction-details">
                <div class="detail-item">
                    <i class="fas fa-clock"></i>
                    <span>Coming Soon</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-envelope"></i>
                    <span>Contact us for more information</span>
                </div>
            </div>

            <div class="construction-actions">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Return to Home
                </a>
                <a href="/contact" class="btn btn-secondary">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.under-construction-page {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
    padding: 40px 0;
}

.construction-content {
    text-align: center;
    max-width: 600px;
    background: white;
    padding: 60px 40px;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: 2px solid #f0f0f0;
}

.construction-icon {
    font-size: 4rem;
    color: var(--primary-color);
    margin-bottom: 30px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.construction-content h1 {
    color: var(--primary-color);
    font-size: 2.5rem;
    margin-bottom: 20px;
    font-family: var(--font-heading);
}

.construction-message h2 {
    color: var(--text-secondary);
    font-size: 1.5rem;
    margin-bottom: 15px;
    font-weight: 600;
}

.construction-message p {
    color: var(--muted-color);
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 40px;
}

.construction-details {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-secondary);
    font-weight: 500;
}

.detail-item i {
    color: var(--primary-color);
    font-size: 1.2rem;
}

.construction-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.construction-actions .btn {
    min-width: 150px;
}

@media (max-width: 768px) {
    .construction-content {
        padding: 40px 20px;
        margin: 20px;
    }

    .construction-content h1 {
        font-size: 2rem;
    }

    .construction-details {
        flex-direction: column;
        gap: 15px;
    }

    .construction-actions {
        flex-direction: column;
        align-items: center;
    }

    .construction-actions .btn {
        width: 100%;
        max-width: 250px;
    }
}
</style>