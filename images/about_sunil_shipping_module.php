<?php
function displayAboutSunilShippingSection() {
    ob_start();
    ?>
    <section id="about-sunil" class='about-container' >
        <div class="container">
            <!-- 제목 -->
           <h2 class="about-title">
    Global leader in vehicle exports<br class='desk-only'> with 30 years of experience
</h2>
            
            <!-- 이미지 + 텍스트 -->
<div class="about-grid" >
    <div class="about-image" style="flex:1.5;">
        <img src="images/container-shipping.png" alt="Container shipping" style="width:100%; height:auto; border-radius:10px; object-fit:cover;">
    </div>
    
    <div class="about-content" style="flex:1; display:flex; flex-direction:column;">
        <p style="color:#505050; line-height:1.8; font-size:18px; text-align:left;">
            With three decades of expertise, we lead global vehicle exports and specialize in fast, secure forwarding to any destination worldwide. Backed by extensive experience and a robust logistics network, we deliver cost-effective, efficient transport solutions. Partner with Sunil Shipping for tailored services that ensure optimal costs and outstanding satisfaction!
        </p>
        
    <div class="info-more-wrap">
    <a href="info/index.php" class="info-more-btn">
        more
    </a>
</div>
    </div>
</div>
        </div>
    </section>

    <style>
        .container {
            max-width: 1400px; margin:0 auto; padding:0 2rem;
        }
        .about-container {
            background:#ffffff; padding: 100px 0 200px 0;
        }
        .info-more-wrap {
    text-align: right;
    margin-top: auto;
}
.about-grid {
    display:flex; align-items:stretch; gap:4rem;
}
.info-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #fff;
    color: #505050;
    border: 1px solid #505050;
    text-decoration: none;
    padding: 7px 40px;
    height: 40px;
    border-radius: 50px;
    font-weight: 600;
    width: 180px
    font-size: 18px;
    transition: all 0.3s;
}

.info-more-btn:hover {
    background: #505050;
    color: #fff;
}

.about-title {
    font-size: 50px;
    font-weight: 600;
    color: #111;
    margin-bottom: 90px;
    line-height: 1.3;
}

.desk-only {
    display: inline;
}


        @media (max-width: 1024px) {
            #about-sunil .about-image { flex-basis: 380px !important; max-width: 380px !important; }
            #about-sunil h2 { font-size: 30px !important; }
        }
        @media (max-width: 768px) {
               .desk-only {
        display: none;
    }

    .about-grid{
        gap: 30px;
    }
    
    .about-title {
        font-size: 28px;
        margin-bottom: 40px;
    }
              .container{
                padding: 0 20px;
              }
                .about-container {
         padding: 50px 0 100px 0;
        }
            #about-sunil .about-grid { flex-direction: column !important; }
            #about-sunil .about-image { flex-basis: auto !important; max-width: 100% !important; }
            #about-sunil p  { font-size: 15px !important; }
             .info-more-wrap {
        text-align: center;
        margin-top: 1rem;
    }

            .info-more-wrap {
    text-align: left;
    margin-top:30px;
}
    
    .info-more-btn {
        padding: 10px 30px;
        font-size: 15px;
        width: 140px;
        height: 35px;
        justify-content: center;
    }
        }
    </style>
    <?php
    return ob_get_clean();
}
?>