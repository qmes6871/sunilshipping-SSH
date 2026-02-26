# WebView 관련 규칙
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}

# 기본 Android 규칙
-keep class androidx.** { *; }
-keep class com.google.android.material.** { *; }
