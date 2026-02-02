<?php
function render_consultant_section() {
    $consultants = [
        [
            'name' => 'Sandra Park',
            'name_ko' => '박산드라',
            'whatsapp' => '+82 10-8583-6033',
            'whatsapp_link' => '821085836033',
            'languages' => ['Russian', 'English', 'Korean'],
            'image' => 'images/sandra.png'
        ],
        [
            'name' => 'Becky',
            'name_ko' => '베키',
            'whatsapp' => '+82 10-3109-6033',
            'whatsapp_link' => '821031096033',
            'languages' => ['Russian', 'English', 'Korean'],
            'image' => 'images/becky.png'
        ],
        [
            'name' => 'Maxim Kim',
            'name_ko' => '김막심',
            'whatsapp' => '+82 10-5009-6033',
            'whatsapp_link' => '821050096033',
            'languages' => ['Russian', 'English', 'Korean'],
            'image' => 'images/maxim.png'
        ]
    ];

    ob_start();
    ?>
    <style>
        .consultant-section {
            background: #fff;
            padding: 60px 20px;
        }
        
        .consultant-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .consultant-header {
            margin-bottom: 50px;
        }
        
        .consultant-header h2 {
            font-size: 50px;
            font-weight: 600;
            color: #111;
            margin-bottom: 10px;
            text-align: left;
        }
        
        .consultant-header .subtitle {
            font-size: 20px;
            color: #505050;
            font-weight: 400;
            text-align: left;
        }
        
        .consultant-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }
        
        .consultant-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 30px;
            transition: box-shadow 0.3s;
        }
        
        .consultant-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .consultant-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .consultant-info {
            flex: 1;
        }
        
        .consultant-name {
            font-size: 22px;
            font-weight: 600;
            color: #111;
            margin-bottom: 5px;
        }
        
        .consultant-name-ko {
            font-size: 16px;
            color: #666;
        }
        
        .consultant-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .consultant-languages {
            margin-bottom: 20px;
        }
        
        .language-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #505050;
            font-size: 16px;
        }
        
        .language-item i {
            color: #25D366;
            font-size: 18px;
        }
        
        .consultant-buttons {
            display: flex;
            gap: 10px;
        }
        
        .consultant-whatsapp {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #3B82F6;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: background 0.2s;
        }
        
        .consultant-whatsapp:hover {
            background: #2563EB;
        }
        
        .consultant-phone {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #111;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .consultant-section {
                padding: 40px 20px;
            }
            
            .consultant-header h2 {
                font-size: 32px;
            }
            
            .consultant-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section class="consultant-section">
        <div class="consultant-container">
            <div class="consultant-header">
                <h2>Customer Service Support</h2>
                <p class="subtitle">Our multilingual support team is here to help you</p>
            </div>
            
            <div class="consultant-grid">
                <?php foreach ($consultants as $consultant): ?>
                    <div class="consultant-card">
                        <div class="consultant-top">
                            <div class="consultant-info">
                                <div class="consultant-name"><?php echo htmlspecialchars($consultant['name']); ?></div>
                                <div class="consultant-name-ko"><?php echo htmlspecialchars($consultant['name_ko']); ?></div>
                            </div>
                            <img src="<?php echo htmlspecialchars($consultant['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($consultant['name']); ?>" 
                                 class="consultant-image">
                        </div>
                        
                        <div class="consultant-languages">
                            <?php foreach ($consultant['languages'] as $lang): ?>
                                <div class="language-item">
                                    <i class="fas fa-check-circle"></i>
                                    <?php echo htmlspecialchars($lang); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="consultant-buttons">
                            <a href="https://wa.me/<?php echo $consultant['whatsapp_link']; ?>" 
                               class="consultant-whatsapp" 
                               target="_blank"
                               rel="noopener noreferrer">
                                <i class="fab fa-whatsapp"></i>
                                WhatsApp
                            </a>
                            <a href="tel:<?php echo str_replace([' ', '-'], '', $consultant['whatsapp']); ?>" 
                               class="consultant-phone">
                                <?php echo htmlspecialchars($consultant['whatsapp']); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
?>