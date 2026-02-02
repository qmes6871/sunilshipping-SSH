<?php
/**
 * Facebook Link Module
 */

function displayFacebookLinkSection() {
    ob_start();
    ?>
    <style>
        #facebook-link {
            width: 100%;
            padding: 0 20px;
            box-sizing: border-box;
            background: #fff;
           padding-bottom: 200px;
        }

        #facebook-link .facebook-link-wrapper {
            display: block;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
                             background: #fff;
        }

        #facebook-link .container {
            background: #F9FAFB;
            border-radius: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 100px 0 100px;
            display: flex;
flex-direction: row;
justify-content: space-between;
            transition: background 0.3s ease;
        }

        #facebook-link .facebook-link-wrapper:hover .container {
            background: #f0f1f2;
        }

        #facebook-link .left-section {
            display: flex;
            flex-direction: column;
            justify-content: end;
            align-items: center;
        }

        #facebook-link .right-section {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .facebook-title {
            font-size: 25px;
            font-weight: 600;
            color: #121212;
            margin-bottom: 10px;
        }

        #facebook-link .description {
            color: #121212;
            font-size: 18px;
            font-weight: 400;
        }

        #facebook-link .username {
            color: #121212;
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 60px;
        }

        .facebook-image {
            width: 350px;
            height: 241px;
        }

        .desk-only {
            display: block;
        }

                @media (max-width: 1034px) {
                                #facebook-link .container {
            padding: 20px 20px 0 20px;
            }

                .desk-only {
            display: none;
        }

            .facebook-title {
                font-size: 20px;
            }

            #facebook-link .description,
            #facebook-link .username {
                font-size: 16px;
            }

                }

        @media (max-width: 768px) {
            #facebook-link .container {
                display: flex;
                flex-direction: column;
            padding: 20px 20px 0 20px;
            gap: 20px;
            }

            .facebook-image {
                width: 100%;
                height: auto;
                max-width: 200px;
            }

                    #facebook-link .left-section {
            display: flex;
        }

                            #facebook-link .right-section {
align-items: center;
padding-bottom: 20px;
                            }

                        .facebook-title {
                font-size: 15px;
            }

            #facebook-link .description {
                font-size: 13px;
                align-items: center;
            }

            #facebook-link .username {
                font-size: 13px;
                margin-bottom: 20px;
            }
        }
    </style>

    <section id="facebook-link">
        <a href="https://www.facebook.com/sunilshippingkorea"
           target="_blank"
           rel="noopener noreferrer"
           class="facebook-link-wrapper">
            <div class="container">
                <div class='left-section'>
                    <img src='/images/facebook.png' alt='Facebook Logo' class='facebook-image' />
                </div>

                <div class="right-section">
                    <h1 class='facebook-title'>Follow our Facebook page﻿</h1>
                    <div class="username">
                        @sunilshippingkorea
                    </div>
                    <div class="description">
                        Check container product details and <br >special discount prices on Facebook.﻿
                    </div>
                </div>
            </div>
        </a>
    </section>
    <?php
    return ob_get_clean();
}
?>