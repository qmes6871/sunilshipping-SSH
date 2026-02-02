<?php
function render_consultant_section() {
    $consultants = [
        [
            'name' => 'Becky',
            'name_ko' => '아흐마드벡',
            'whatsapp' => '+82 10-3109-6033',
            'whatsapp_link' => '821031096033',
            'languages' => ['Russian', 'English', 'Korean'],
            'image' => 'images/sandra.png'
        ],
        [
            'name' => 'Artem',
            'name_ko' => '아르쫌',
            'whatsapp' => '+82 10-8583-6033',
            'whatsapp_link' => '821085836033',
            'languages' => ['Russian', 'English', 'Korean'],
            'image' => 'images/artem.jpg'
        ],
        [
            'name' => 'Buyong Park',
            'name_ko' => '박부용',
            'whatsapp' => '+82 10-3109-8860',
            'whatsapp_link' => '821031098860',
            'languages' => ['Russian', 'English', 'Korean'],
            'image' => 'images/buyong.jpg'
        ]
    ];

    ob_start();
    ?>
    <style>
        .consultant-section {
            background: #fff;
            padding: 100px 20px 170px 20px;
        }
        
        .consultant-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .consultant-header {
            margin-bottom: 120px;
        }
        
        .consultant-header h2 {
            font-size: 32px;
            font-weight: 600;
            color: #111;
            margin-bottom: 10px;
            text-align: left;
        }
        
        .consultant-header .subtitle {
            font-size: 16px;
            color: #505050;
            font-weight: 400;
            text-align: left;
        }
        
        .consultant-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .consultant-card {
            background: white;
            border: 1px solid #c8c8c8;
            border-radius: 10px;
            padding: 30px;
            transition: box-shadow 0.3s;
            box-shadow: 2px 2px 10px 0 rgba(0, 0, 0, 0.1);
        }
        
        .consultant-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .consultant-top {
            display: flex;
            justify-content: space-between;
        }
        
        .consultant-info {
            flex: 1;
        }
        
        .consultant-name {
            font-size:18px;
            font-weight: 500;
            color: #121212;
            margin-bottom: 9px;
        }
        
        .consultant-name-ko {
            font-size: 16px;
            color: #505050;
                        font-weight: 400;
        }
        
        .consultant-image {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .consultant-languages {
            margin-bottom: 20px;
        }
        
        .language-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #505050;
            font-size: 16px;
            font-weight: 300;
        }


.check-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #505050;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    position: relative;
}

.check-icon::after {
    content: '✓';
    color: white;
    font-size: 16px;
    font-weight: bold;
}
        
        .language-item i {
            color: #505050;
            font-size: 18px;
        }
        
        .consultant-buttons {
            display: flex;
            gap: 10px;
            margin-top: 80px;
        }
        
.consultant-whatsapp {
    flex: 1;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid #eee;
    background: #EEEEEE;
    color: #3B82F6;
 padding: 0 15px; 
    border-radius: 30px;
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    transition: background 0.2s;
    white-space: nowrap;
    box-sizing: border-box;
}

.consultant-whatsapp:hover {
    background: #fff;
    border: 1px solid #3B82F6;
}

.consultant-phone {
    flex: 1;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #121212;
    color: white;
    padding: 0 15px;
    border-radius: 30px;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    white-space: nowrap;
    box-sizing: border-box;
    line-height: 40px;
}


        .whatsapp-icon {
        width: 30px; height: 30px;
        }

        @media (max-width: 1380px) {
            .consultant-grid {
                 grid-template-columns: repeat(2, 1fr);
            }
        }
        
@media (max-width: 768px) {
    .consultant-section {
        padding: 50px 20px 100px 20px;
    }

    .consultant-image {
        width: 100px;
        height: 100px;
    }

    .consultant-card {
        padding: 20px;
        width: 100%;
    }
    
    .consultant-header {
        margin-bottom: 60px;
    }
    
    .consultant-header h2 {
        font-size: 20px;
    }
    
    .consultant-header .subtitle {
        font-size: 14px;
    }

     .consultant-buttons {
        flex-direction: column;
        gap: 10px;
    }

    .check-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #505050;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    position: relative;
}

.check-icon::after {
    content: '✓';
    color: white;
    font-size: 14px;
    font-weight: bold;
}

    .consultant-phone {
        display: flex !important;
        width: 100%;
        font-size: 14px;
        height: 45px !important;
        box-sizing: border-box !important; 
    }

        .consultant-whatsapp{
              display: flex !important;
        width: 100%;
        font-size: 14px;
        height: 45px !important;
        padding: 5px 15px !important; 
        box-sizing: border-box !important; 
        }

    .consultant-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

         .whatsapp-icon {
        width: 24px; height: 24px;
        }

               .consultant-name {
            font-size: 16px;
        }

        .language-item {
            font-size: 14px;
            gap: 8px;
        }

        .consultant-top {
            margin-bottom: 0px;
        }

         .consultant-name-ko {
            font-size: 14px;}

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
            <span class="check-icon"></span>
            <?php echo htmlspecialchars($lang); ?>
        </div>
    <?php endforeach; ?>
</div>
                        
                        <div class="consultant-buttons">
<a href="https://wa.me/<?php echo $consultant['whatsapp_link']; ?>" 
   class="consultant-whatsapp" 
   target="_blank"
   rel="noopener noreferrer"> WhatsApp
    <img src="images/whats-app-icon.svg" alt="WhatsApp"class="whatsapp-icon">
   
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