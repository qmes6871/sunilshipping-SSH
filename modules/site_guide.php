<?php
// 사이트 이용 안내 모듈
// 사용 방법: include __DIR__.'/modules/site_guide.php';
?>

<style>
    .guide-section {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .guide-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .guide-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 15px;
        letter-spacing: -0.5px;
    }

    .guide-subtitle {
        font-size: 1.1rem;
        color: #6b7280;
        font-weight: 400;
    }

    .guide-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }

    .guide-step {
        background: white;
        border-radius: 16px;
        padding: 35px 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        position: relative;
        border: 2px solid transparent;
    }

    .guide-step:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        border-color: #2563eb;
    }

    .step-number {
        position: absolute;
        top: -15px;
        left: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
    }

    .step-icon {
        font-size: 3rem;
        color: #2563eb;
        margin: 20px 0 25px;
        text-align: center;
    }

    .step-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 15px;
        text-align: center;
    }

    .step-description {
        font-size: 1rem;
        line-height: 1.7;
        color: #4b5563;
        text-align: center;
    }

    /* 반응형 */
    @media (max-width: 768px) {
        .guide-title {
            font-size: 2rem;
        }

        .guide-steps {
            grid-template-columns: 1fr;
            gap: 40px;
        }

        .guide-step {
            padding: 40px 25px 30px;
        }
    }

    @media (min-width: 1024px) {
        .guide-steps {
            grid-template-columns: repeat(4, 1fr);
        }
    }
</style>

<section class="guide-section">
    <div class="guide-header">
        <h2 class="guide-title">서비스 이용 안내</h2>
        <p class="guide-subtitle">간단한 4단계로 해상운송 예약이 완료됩니다</p>
    </div>

    <div class="guide-steps">
        <!-- 1단계 -->
        <div class="guide-step">
            <div class="step-number">1</div>
            <div class="step-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h3 class="step-title">로그인하기</h3>
            <p class="step-description">
                회원 로그인 후 서비스 이용이 가능합니다. 아직 회원이 아니시라면 간단한 가입 절차를 통해 바로 시작하실 수 있습니다.
            </p>
        </div>

        <!-- 2단계 -->
        <div class="guide-step">
            <div class="step-number">2</div>
            <div class="step-icon">
                <i class="fas fa-ship"></i>
            </div>
            <h3 class="step-title">상품 선택 및 예약 요청</h3>
            <p class="step-description">
                원하시는 목적지와 일정에 맞춰, 가장 저렴하고 효율적인 컨테이너 상품을 선택하세요. 상품 상세 페이지에서 운송 옵션과 예상 견적을 바로 확인할 수 있습니다.
            </p>
        </div>

        <!-- 3단계 -->
        <div class="guide-step">
            <div class="step-number">3</div>
            <div class="step-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="step-title">예약 확정하기</h3>
            <p class="step-description">
                선택한 상품을 확인한 후 '예약하기' 버튼을 클릭하면 예약 요청이 완료됩니다. 담당자가 확인 후 발송 일정 및 결제 안내를 드립니다.
            </p>
        </div>

        <!-- 4단계 -->
        <div class="guide-step">
            <div class="step-number">4</div>
            <div class="step-icon">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <h3 class="step-title">예약 완료 및 트래킹</h3>
            <p class="step-description">
                예약이 확정되면 '마이페이지(My Page)'에서 진행 상태와 트래킹 리포트를 실시간으로 확인하실 수 있습니다. 컨테이너 출발 후 자동으로 위치 정보가 업데이트됩니다.
            </p>
        </div>
    </div>
</section>
