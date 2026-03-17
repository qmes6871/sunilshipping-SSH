package com.sunilshippingcrm.crm;

import android.Manifest;
import android.app.Activity;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.provider.MediaStore;
import android.view.KeyEvent;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.GeolocationPermissions;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.firebase.messaging.FirebaseMessaging;

import java.io.File;
import java.io.IOException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

public class MainActivity extends AppCompatActivity {

    private static final String CRM_URL = "https://sunilshipping.mycafe24.com/crm";
    private static final int FILE_CHOOSER_REQUEST_CODE = 100;
    private static final int PERMISSION_REQUEST_CODE = 101;

    private WebView webView;
    private ProgressBar progressBar;
    private SwipeRefreshLayout swipeRefreshLayout;
    private View errorView;

    private ValueCallback<Uri[]> filePathCallback;
    private String cameraPhotoPath;
    private String pendingNotificationUrl = null;
    private boolean isInitialLoad = true;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        initViews();
        setupWebView();
        checkPermissions();
        initFirebase();

        // 알림에서 앱을 열었을 때 URL 저장 (나중에 로그인 확인 후 이동)
        checkNotificationIntent(getIntent());

        // 항상 메인 URL부터 로드 (세션/쿠키 확인용)
        loadUrl();
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        // 앱이 이미 실행 중일 때는 바로 알림 URL로 이동 가능
        if (intent != null && intent.hasExtra("notification_url")) {
            String url = intent.getStringExtra("notification_url");
            if (url != null && !url.isEmpty()) {
                progressBar.setVisibility(View.VISIBLE);
                webView.loadUrl(url);
            }
        }
    }

    private void checkNotificationIntent(Intent intent) {
        if (intent != null && intent.hasExtra("notification_url")) {
            String url = intent.getStringExtra("notification_url");
            if (url != null && !url.isEmpty()) {
                pendingNotificationUrl = url;
                android.util.Log.d("Notification", "Pending notification URL: " + url);
            }
        }
    }

    private void initFirebase() {
        // FCM 토큰 가져오기
        FirebaseMessaging.getInstance().getToken()
            .addOnCompleteListener(task -> {
                if (!task.isSuccessful()) {
                    android.util.Log.w("FCM", "Fetching FCM token failed", task.getException());
                    return;
                }

                // 새 FCM 토큰 가져오기
                String token = task.getResult();
                android.util.Log.d("FCM", "FCM Token: " + token);

                // 토큰을 SharedPreferences에 저장
                getSharedPreferences("fcm_prefs", MODE_PRIVATE)
                    .edit()
                    .putString("fcm_token", token)
                    .apply();
            });

        // 토픽 구독 (전체 알림용)
        FirebaseMessaging.getInstance().subscribeToTopic("all_users")
            .addOnCompleteListener(task -> {
                if (task.isSuccessful()) {
                    android.util.Log.d("FCM", "Subscribed to all_users topic");
                }
            });
    }

    private void initViews() {
        webView = findViewById(R.id.webView);
        progressBar = findViewById(R.id.progressBar);
        swipeRefreshLayout = findViewById(R.id.swipeRefreshLayout);
        errorView = findViewById(R.id.errorView);

        // 스와이프 새로고침 설정
        swipeRefreshLayout.setColorSchemeResources(R.color.primary);
        swipeRefreshLayout.setOnRefreshListener(() -> {
            webView.reload();
        });

        // 에러 화면 재시도 버튼
        findViewById(R.id.btnRetry).setOnClickListener(v -> {
            errorView.setVisibility(View.GONE);
            webView.setVisibility(View.VISIBLE);
            loadUrl();
        });
    }

    private void setupWebView() {
        WebSettings webSettings = webView.getSettings();

        // JavaScript 활성화
        webSettings.setJavaScriptEnabled(true);
        webSettings.setJavaScriptCanOpenWindowsAutomatically(true);

        // DOM Storage 활성화
        webSettings.setDomStorageEnabled(true);
        webSettings.setDatabaseEnabled(true);

        // 캐시 설정
        webSettings.setCacheMode(WebSettings.LOAD_DEFAULT);

        // 줌 설정
        webSettings.setSupportZoom(true);
        webSettings.setBuiltInZoomControls(true);
        webSettings.setDisplayZoomControls(false);

        // 기타 설정
        webSettings.setLoadWithOverviewMode(true);
        webSettings.setUseWideViewPort(true);
        webSettings.setAllowFileAccess(true);
        webSettings.setAllowContentAccess(true);
        webSettings.setMediaPlaybackRequiresUserGesture(false);

        // Mixed content 허용 (HTTP + HTTPS)
        webSettings.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);

        // 위치 정보 활성화
        webSettings.setGeolocationEnabled(true);

        // 쿠키 허용 및 세션 유지 강화
        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);
        cookieManager.setAcceptThirdPartyCookies(webView, true);
        // 쿠키 동기화 (앱 시작 시 저장된 쿠키 로드)
        cookieManager.flush();

        // WebViewClient 설정
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                String url = request.getUrl().toString();

                // 외부 링크는 기본 브라우저로 열기
                if (!url.contains("sunilshipping.mycafe24.com")) {
                    Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                    startActivity(intent);
                    return true;
                }

                return false;
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                swipeRefreshLayout.setRefreshing(false);
                progressBar.setVisibility(View.GONE);

                // 쿠키 동기화 (중요: 세션 유지를 위해)
                CookieManager.getInstance().flush();

                // 초기 로드 시 알림 URL 처리
                if (isInitialLoad && pendingNotificationUrl != null) {
                    isInitialLoad = false;

                    // 로그인 페이지가 아니면 (이미 로그인된 상태) 알림 URL로 이동
                    if (!url.contains("/login") && !url.contains("login.php")) {
                        String targetUrl = pendingNotificationUrl;
                        pendingNotificationUrl = null;
                        android.util.Log.d("Notification", "Navigating to notification URL: " + targetUrl);
                        progressBar.setVisibility(View.VISIBLE);
                        view.loadUrl(targetUrl);
                    } else {
                        // 로그인 페이지면 pendingNotificationUrl 유지
                        // 로그인 후 다시 이 메소드가 호출되면 알림 URL로 이동
                        android.util.Log.d("Notification", "Login required, keeping pending URL");
                        isInitialLoad = true; // 로그인 후 다시 체크하도록
                    }
                }
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
                super.onReceivedError(view, request, error);
                if (request.isForMainFrame()) {
                    showErrorView();
                }
            }
        });

        // WebChromeClient 설정 (파일 업로드, 진행률 등)
        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                progressBar.setProgress(newProgress);
                if (newProgress == 100) {
                    progressBar.setVisibility(View.GONE);
                } else {
                    progressBar.setVisibility(View.VISIBLE);
                }
            }

            // 파일 업로드 처리
            @Override
            public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> filePathCallback,
                                             FileChooserParams fileChooserParams) {
                MainActivity.this.filePathCallback = filePathCallback;
                openFileChooser();
                return true;
            }

            // 위치 정보 권한 요청
            @Override
            public void onGeolocationPermissionsShowPrompt(String origin,
                                                           GeolocationPermissions.Callback callback) {
                callback.invoke(origin, true, false);
            }

            // Alert 다이얼로그
            @Override
            public boolean onJsAlert(WebView view, String url, String message, android.webkit.JsResult result) {
                new AlertDialog.Builder(MainActivity.this)
                        .setTitle("알림")
                        .setMessage(message)
                        .setPositiveButton("확인", (dialog, which) -> result.confirm())
                        .setCancelable(false)
                        .show();
                return true;
            }

            // Confirm 다이얼로그
            @Override
            public boolean onJsConfirm(WebView view, String url, String message, android.webkit.JsResult result) {
                new AlertDialog.Builder(MainActivity.this)
                        .setTitle("확인")
                        .setMessage(message)
                        .setPositiveButton("확인", (dialog, which) -> result.confirm())
                        .setNegativeButton("취소", (dialog, which) -> result.cancel())
                        .setCancelable(false)
                        .show();
                return true;
            }
        });
    }

    private void loadUrl() {
        progressBar.setVisibility(View.VISIBLE);
        webView.loadUrl(CRM_URL);
    }

    private void showErrorView() {
        webView.setVisibility(View.GONE);
        errorView.setVisibility(View.VISIBLE);
        swipeRefreshLayout.setRefreshing(false);
        progressBar.setVisibility(View.GONE);
    }

    private void checkPermissions() {
        // Android 13+ 알림 권한 요청
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS)
                    != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(this,
                    new String[]{Manifest.permission.POST_NOTIFICATIONS}, 102);
            }
        }

        String[] permissions = {
                Manifest.permission.CAMERA,
                Manifest.permission.READ_EXTERNAL_STORAGE,
                Manifest.permission.ACCESS_FINE_LOCATION
        };

        boolean allGranted = true;
        for (String permission : permissions) {
            if (ContextCompat.checkSelfPermission(this, permission) != PackageManager.PERMISSION_GRANTED) {
                allGranted = false;
                break;
            }
        }

        if (!allGranted) {
            ActivityCompat.requestPermissions(this, permissions, PERMISSION_REQUEST_CODE);
        }
    }

    private void openFileChooser() {
        Intent takePictureIntent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
        if (takePictureIntent.resolveActivity(getPackageManager()) != null) {
            File photoFile = null;
            try {
                photoFile = createImageFile();
            } catch (IOException ex) {
                ex.printStackTrace();
            }

            if (photoFile != null) {
                Uri photoUri = FileProvider.getUriForFile(this,
                        getApplicationContext().getPackageName() + ".fileprovider",
                        photoFile);
                takePictureIntent.putExtra(MediaStore.EXTRA_OUTPUT, photoUri);
            }
        }

        Intent contentSelectionIntent = new Intent(Intent.ACTION_GET_CONTENT);
        contentSelectionIntent.addCategory(Intent.CATEGORY_OPENABLE);
        contentSelectionIntent.setType("*/*");
        contentSelectionIntent.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);

        Intent[] intentArray;
        if (takePictureIntent.resolveActivity(getPackageManager()) != null) {
            intentArray = new Intent[]{takePictureIntent};
        } else {
            intentArray = new Intent[0];
        }

        Intent chooserIntent = new Intent(Intent.ACTION_CHOOSER);
        chooserIntent.putExtra(Intent.EXTRA_INTENT, contentSelectionIntent);
        chooserIntent.putExtra(Intent.EXTRA_TITLE, "파일 선택");
        chooserIntent.putExtra(Intent.EXTRA_INITIAL_INTENTS, intentArray);

        startActivityForResult(chooserIntent, FILE_CHOOSER_REQUEST_CODE);
    }

    private File createImageFile() throws IOException {
        String timeStamp = new SimpleDateFormat("yyyyMMdd_HHmmss", Locale.getDefault()).format(new Date());
        String imageFileName = "JPEG_" + timeStamp + "_";
        File storageDir = getExternalFilesDir(Environment.DIRECTORY_PICTURES);
        File image = File.createTempFile(imageFileName, ".jpg", storageDir);
        cameraPhotoPath = image.getAbsolutePath();
        return image;
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == FILE_CHOOSER_REQUEST_CODE) {
            if (filePathCallback == null) return;

            Uri[] results = null;
            if (resultCode == Activity.RESULT_OK) {
                if (data == null || data.getData() == null) {
                    // 카메라로 촬영한 경우
                    if (cameraPhotoPath != null) {
                        results = new Uri[]{Uri.parse(cameraPhotoPath)};
                    }
                } else {
                    // 갤러리에서 선택한 경우
                    String dataString = data.getDataString();
                    if (dataString != null) {
                        results = new Uri[]{Uri.parse(dataString)};
                    }
                }
            }

            filePathCallback.onReceiveValue(results);
            filePathCallback = null;
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions,
                                           @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == PERMISSION_REQUEST_CODE) {
            // 권한 결과 처리
        }
    }

    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        // 뒤로가기 버튼 처리
        if (keyCode == KeyEvent.KEYCODE_BACK && webView.canGoBack()) {
            webView.goBack();
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.onResume();
    }

    @Override
    protected void onPause() {
        super.onPause();
        webView.onPause();
        // 쿠키 저장 (앱 종료/백그라운드 시 세션 유지)
        CookieManager.getInstance().flush();
    }

    @Override
    protected void onDestroy() {
        if (webView != null) {
            webView.destroy();
        }
        super.onDestroy();
    }
}
