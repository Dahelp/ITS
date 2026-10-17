package ru.itscenter.its_avito

import io.flutter.embedding.android.FlutterActivity
import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import android.os.Bundle

class MainActivity: FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel("avito_messages", "Сообщения Авито", NotificationManager.IMPORTANCE_HIGH)
            channel.description = "Новые сообщения покупателей"
            getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
        }
    }
}
