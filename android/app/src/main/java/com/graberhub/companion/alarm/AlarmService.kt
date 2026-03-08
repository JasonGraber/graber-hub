package com.graberhub.companion.alarm

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Intent
import android.media.AudioAttributes
import android.media.MediaPlayer
import android.media.RingtoneManager
import android.os.Build
import android.os.Handler
import android.os.IBinder
import android.os.Looper
import androidx.core.app.NotificationCompat
import com.graberhub.companion.MainActivity

class AlarmService : Service() {

    companion object {
        const val ACTION_START = "com.graberhub.companion.ALARM_START"
        const val ACTION_STOP = "com.graberhub.companion.ALARM_STOP"
        const val CHANNEL_ID = "timer_alarm_channel"
        const val NOTIFICATION_ID = 1001
        const val ALARM_DURATION_MS = 15000L
    }

    private var mediaPlayer: MediaPlayer? = null
    private val stopHandler = Handler(Looper.getMainLooper())
    private val autoStopRunnable = Runnable { stopAlarmAndSelf() }

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        when (intent?.action) {
            ACTION_STOP -> stopAlarmAndSelf()
            else -> startAlarm()
        }
        return START_NOT_STICKY
    }

    private fun startAlarm() {
        val openIntent = Intent(this, MainActivity::class.java).apply {
            this.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
        }
        val openPi = PendingIntent.getActivity(this, 0, openIntent, PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT)

        val stopIntent = Intent(this, AlarmService::class.java).apply {
            action = ACTION_STOP
        }
        val stopPi = PendingIntent.getService(this, 1, stopIntent, PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT)

        val notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_lock_idle_alarm)
            .setContentTitle("⏰ Timer Done!")
            .setContentText("Tap to open app or stop the alarm.")
            .setContentIntent(openPi)
            .addAction(android.R.drawable.ic_delete, "Stop Alarm", stopPi)
            .setOngoing(true)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_ALARM)
            .setAutoCancel(false)
            .build()

        if (Build.VERSION.SDK_INT >= 29) {
            startForeground(NOTIFICATION_ID, notification, 2) // FOREGROUND_SERVICE_TYPE_MEDIA_PLAYBACK
        } else {
            startForeground(NOTIFICATION_ID, notification)
        }

        val alarmUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM)
            ?: RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)

        try {
            mediaPlayer = MediaPlayer().apply {
                setAudioAttributes(
                    AudioAttributes.Builder()
                        .setUsage(AudioAttributes.USAGE_ALARM)
                        .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                        .build()
                )
                setDataSource(this@AlarmService, alarmUri)
                isLooping = true
                prepare()
                start()
            }
        } catch (_: Exception) {}

        stopHandler.removeCallbacks(autoStopRunnable)
        stopHandler.postDelayed(autoStopRunnable, ALARM_DURATION_MS)
    }

    private fun stopAlarmAndSelf() {
        stopHandler.removeCallbacks(autoStopRunnable)
        mediaPlayer?.let {
            try {
                it.stop()
                it.release()
            } catch (_: Exception) {}
        }
        mediaPlayer = null
        stopForeground(true)
        stopSelf()

        val broadcastIntent = Intent(ACTION_STOP).apply {
            setPackage(packageName)
        }
        sendBroadcast(broadcastIntent)
    }

    override fun onDestroy() {
        stopAlarmAndSelf()
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null

    private fun createNotificationChannel() {
        val channel = NotificationChannel(
            CHANNEL_ID,
            "Timer Alarm",
            NotificationManager.IMPORTANCE_HIGH
        ).apply {
            description = "Plays when a timer finishes"
            setSound(null, null)
            enableVibration(true)
        }
        getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
    }
}
