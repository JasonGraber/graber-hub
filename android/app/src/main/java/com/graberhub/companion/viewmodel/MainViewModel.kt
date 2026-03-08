package com.graberhub.companion.viewmodel

import android.app.Application
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.os.Build
import android.util.Log
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.graberhub.companion.alarm.AlarmService
import com.graberhub.companion.data.models.*
import com.graberhub.companion.data.prefs.PrefsManager
import com.graberhub.companion.network.RetrofitClient
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.*

enum class ConnectionState {
    UNKNOWN, CONNECTING, CONNECTED, FAILED
}

class MainViewModel(application: Application) : AndroidViewModel(application) {

    val prefs = PrefsManager(application)

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error.asStateFlow()

    private val _kids = MutableStateFlow<List<Kid>>(emptyList())
    val kids: StateFlow<List<Kid>> = _kids.asStateFlow()

    private val _schedule = MutableStateFlow<List<ScheduleBlock>>(emptyList())
    val schedule: StateFlow<List<ScheduleBlock>> = _schedule.asStateFlow()

    private val _countdowns = MutableStateFlow<List<Countdown>>(emptyList())
    val countdowns: StateFlow<List<Countdown>> = _countdowns.asStateFlow()

    private val _verse = MutableStateFlow<Verse?>(null)
    val verse: StateFlow<Verse?> = _verse.asStateFlow()

    private val _verses = MutableStateFlow<List<Verse>>(emptyList())
    val verses: StateFlow<List<Verse>> = _verses.asStateFlow()

    private val _connectionStatus = MutableStateFlow<String?>(null)
    val connectionStatus: StateFlow<String?> = _connectionStatus.asStateFlow()

    private val _connectionState = MutableStateFlow(ConnectionState.UNKNOWN)
    val connectionState: StateFlow<ConnectionState> = _connectionState.asStateFlow()

    private val _activeNetwork = MutableStateFlow<String?>(null)
    val activeNetwork: StateFlow<String?> = _activeNetwork.asStateFlow()

    private val _kidsConfig = MutableStateFlow(prefs.kidsConfig)
    val kidsConfig: StateFlow<List<KidConfig>> = _kidsConfig.asStateFlow()

    private val _timerStatus = MutableStateFlow<TimerStatus?>(null)
    val timerStatus: StateFlow<TimerStatus?> = _timerStatus.asStateFlow()

    private val _timerSecondsLeft = MutableStateFlow(0L)
    val timerSecondsLeft: StateFlow<Long> = _timerSecondsLeft.asStateFlow()

    private val _timerFinished = MutableStateFlow(false)
    val timerFinished: StateFlow<Boolean> = _timerFinished.asStateFlow()

    private val _alarmPlaying = MutableStateFlow(false)
    val alarmPlaying: StateFlow<Boolean> = _alarmPlaying.asStateFlow()

    private val _photoSettings = MutableStateFlow<PhotoSettings?>(null)
    val photoSettings: StateFlow<PhotoSettings?> = _photoSettings.asStateFlow()

    private val _photoSettingsSaved = MutableStateFlow<String?>(null)
    val photoSettingsSaved: StateFlow<String?> = _photoSettingsSaved.asStateFlow()

    private val _displayMode = MutableStateFlow("dashboard")
    val displayMode: StateFlow<String> = _displayMode.asStateFlow()

    private var displayModePollJob: Job? = null
    private var timerTickJob: Job? = null
    private var alarmAutoStopJob: Job? = null

    private val alarmStopReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            _alarmPlaying.value = false
            alarmAutoStopJob?.cancel()
        }
    }

    init {
        updateActiveNetworkLabel()
        connectWithFallback()
        loadTimer()
        startDisplayModePoll()

        val filter = IntentFilter(AlarmService.ACTION_STOP)
        if (Build.VERSION.SDK_INT >= 33) {
            getApplication<Application>().registerReceiver(alarmStopReceiver, filter, Context.RECEIVER_NOT_EXPORTED)
        } else {
            getApplication<Application>().registerReceiver(alarmStopReceiver, filter)
        }
    }

    override fun onCleared() {
        super.onCleared()
        displayModePollJob?.cancel()
        try {
            getApplication<Application>().unregisterReceiver(alarmStopReceiver)
        } catch (_: Exception) {}
    }

    private fun startDisplayModePoll() {
        displayModePollJob?.cancel()
        displayModePollJob = viewModelScope.launch {
            while (true) {
                try {
                    val response = RetrofitClient.getApiService().getPhotoSettings()
                    if (response.isSuccessful) {
                        response.body()?.let { settings ->
                            _displayMode.value = settings.mode ?: "dashboard"
                            _photoSettings.value = settings
                        }
                    }
                } catch (e: Exception) {
                    Log.d("MainViewModel", "Display mode poll failed: ${e.message}")
                }
                delay(5000)
            }
        }
    }

    private fun initRetrofit() {
        val url = prefs.serverUrl
        Log.d("MainViewModel", "initRetrofit() with url=$url")
        RetrofitClient.init(url)
    }

    private fun updateActiveNetworkLabel() {
        _activeNetwork.value = when (prefs.serverUrl) {
            PrefsManager.HOME_URL -> "home"
            PrefsManager.TAILSCALE_URL -> "tailscale"
            else -> "custom"
        }
    }

    /**
     * Auto-fallback connection logic:
     * 1. Try saved URL
     * 2. If fails, try alternate URL
     * 3. If alternate works, save it
     * 4. If both fail, show error
     */
    private fun connectWithFallback() {
        viewModelScope.launch {
            _connectionState.value = ConnectionState.CONNECTING
            _connectionStatus.value = "Connecting..."

            // Try saved URL first
            val savedUrl = prefs.serverUrl
            Log.d("MainViewModel", "connectWithFallback: trying saved URL=$savedUrl")
            RetrofitClient.init(savedUrl)

            if (RetrofitClient.testConnection()) {
                Log.d("MainViewModel", "connectWithFallback: saved URL connected!")
                _connectionState.value = ConnectionState.CONNECTED
                _connectionStatus.value = "✓ Connected"
                updateActiveNetworkLabel()
                loadDashboard()
                return@launch
            }

            // Try alternate URL
            val altUrl = prefs.alternateUrl
            Log.d("MainViewModel", "connectWithFallback: saved URL failed, trying alternate=$altUrl")
            RetrofitClient.init(altUrl)

            if (RetrofitClient.testConnection()) {
                Log.d("MainViewModel", "connectWithFallback: alternate URL connected!")
                prefs.serverUrl = altUrl
                _connectionState.value = ConnectionState.CONNECTED
                _connectionStatus.value = "✓ Connected (switched network)"
                updateActiveNetworkLabel()
                loadDashboard()
                return@launch
            }

            // Both failed
            Log.d("MainViewModel", "connectWithFallback: both URLs failed")
            // Reset to saved URL
            RetrofitClient.init(savedUrl)
            _connectionState.value = ConnectionState.FAILED
            _connectionStatus.value = "✗ Connection failed"
            _error.value = "Cannot reach server. Check your network."
        }
    }

    fun today(): String = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Date())

    // ── Dashboard ──────────────────────────────────────────

    fun loadDashboard() {
        viewModelScope.launch {
            try {
                _isLoading.value = true
                _error.value = null
                val response = RetrofitClient.getApiService().getDashboard()
                if (response.isSuccessful) {
                    response.body()?.let { dashboard ->
                        _kids.value = dashboard.kids
                        _schedule.value = dashboard.schedule.sortedBy { it.time_start }
                        _countdowns.value = dashboard.countdowns
                        _verse.value = dashboard.verse
                    }
                    _connectionState.value = ConnectionState.CONNECTED
                } else {
                    _error.value = "Server error: ${response.code()}"
                }
            } catch (e: Exception) {
                _error.value = "Connection failed: ${e.message}"
                _connectionState.value = ConnectionState.FAILED
            } finally {
                _isLoading.value = false
            }
        }
    }

    // ── Chores ─────────────────────────────────────────────

    fun toggleChore(choreId: Int) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().toggleChore(choreId, today())
                loadDashboard()
            } catch (_: Exception) {
                _error.value = "Failed to update chore"
            }
        }
    }

    fun addChore(kidId: Int, title: String, emoji: String = "") {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().createChore(
                    CreateChoreRequest(kidId, title, today(), emoji)
                )
                loadDashboard()
            } catch (_: Exception) {
                _error.value = "Failed to add chore"
            }
        }
    }

    fun editChore(choreId: Int, title: String, emoji: String) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().updateChore(choreId, UpdateChoreRequest(title, emoji))
                loadDashboard()
            } catch (_: Exception) {
                _error.value = "Failed to edit chore"
            }
        }
    }

    fun deleteChore(choreId: Int) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().deleteChore(choreId, today())
                loadDashboard()
            } catch (_: Exception) {
                _error.value = "Failed to delete chore"
            }
        }
    }

    // ── Schedule ───────────────────────────────────────────

    fun addScheduleBlock(timeStart: String, timeEnd: String, title: String, emoji: String) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().createScheduleBlock(
                    CreateScheduleRequest(timeStart, timeEnd, title, emoji, today())
                )
                loadDashboard()
            } catch (_: Exception) {
                _error.value = "Failed to add schedule block"
            }
        }
    }

    fun editScheduleBlock(blockId: Int, title: String, emoji: String, timeStart: String, timeEnd: String) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().updateScheduleBlock(
                    blockId, UpdateScheduleRequest(title, emoji, timeStart, timeEnd)
                )
                loadDashboard()
            } catch (_: Exception) {
                _error.value = "Failed to edit schedule block"
            }
        }
    }

    fun deleteScheduleBlock(blockId: Int) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().deleteScheduleBlock(blockId, today())
                loadDashboard()
            } catch (_: Exception) {
                _error.value = "Failed to delete schedule block"
            }
        }
    }

    fun applyTemplate() {
        viewModelScope.launch {
            try {
                val blocks = _schedule.value.map {
                    CreateScheduleRequest(it.time_start, it.time_end, it.title, it.emoji)
                }
                RetrofitClient.getApiService().applyTemplate(TemplateRequest(blocks))
                _error.value = null
                loadDashboard()
            } catch (_: Exception) {
                _error.value = "Failed to save template"
            }
        }
    }

    // ── Countdowns ─────────────────────────────────────────

    fun loadCountdowns() {
        viewModelScope.launch {
            try {
                val response = RetrofitClient.getApiService().getCountdowns()
                if (response.isSuccessful) {
                    _countdowns.value = response.body() ?: emptyList()
                }
            } catch (_: Exception) {
                _error.value = "Failed to load countdowns"
            }
        }
    }

    fun addCountdown(label: String, emoji: String, targetDate: String) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().createCountdown(
                    CreateCountdownRequest(label, emoji, targetDate)
                )
                loadCountdowns()
            } catch (_: Exception) {
                _error.value = "Failed to add countdown"
            }
        }
    }

    fun deleteCountdown(id: Int) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().deleteCountdown(id)
                loadCountdowns()
            } catch (_: Exception) {
                _error.value = "Failed to delete countdown"
            }
        }
    }

    // ── Verses ─────────────────────────────────────────────

    fun loadVerses() {
        viewModelScope.launch {
            try {
                val response = RetrofitClient.getApiService().getVerses()
                if (response.isSuccessful) {
                    _verses.value = response.body() ?: emptyList()
                }
            } catch (_: Exception) {
                _error.value = "Failed to load verses"
            }
        }
    }

    fun addVerse(text: String, reference: String) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().createVerse(CreateVerseRequest(text, reference))
                loadVerses()
            } catch (_: Exception) {
                _error.value = "Failed to add verse"
            }
        }
    }

    // ── Timer ──────────────────────────────────────────────

    fun loadTimer() {
        viewModelScope.launch {
            try {
                val response = RetrofitClient.getApiService().getTimer()
                if (response.isSuccessful) {
                    val status = response.body()
                    _timerStatus.value = status
                    if (status?.active == true && status.end_time != null) {
                        startLocalCountdown(status.end_time)
                    }
                }
            } catch (_: Exception) {}
        }
    }

    fun startTimer(minutes: Int, label: String) {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().createTimer(CreateTimerRequest(minutes, label))
                _timerFinished.value = false
                loadTimer()
            } catch (_: Exception) {
                _error.value = "Failed to start timer"
            }
        }
    }

    fun cancelTimer() {
        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().deleteTimer()
                timerTickJob?.cancel()
                timerTickJob = null
                _timerStatus.value = null
                _timerSecondsLeft.value = 0
                _timerFinished.value = false
                stopAlarmSound()
            } catch (_: Exception) {
                _error.value = "Failed to cancel timer"
            }
        }
    }

    fun dismissTimerFinished() {
        stopAlarmSound()
        _timerFinished.value = false
    }

    fun startAlarmSound() {
        _alarmPlaying.value = true
        val app = getApplication<Application>()
        val intent = Intent(app, AlarmService::class.java).apply {
            action = AlarmService.ACTION_START
        }
        app.startForegroundService(intent)

        alarmAutoStopJob?.cancel()
        alarmAutoStopJob = viewModelScope.launch {
            delay(15000)
            _alarmPlaying.value = false
        }
    }

    fun stopAlarmSound() {
        alarmAutoStopJob?.cancel()
        _alarmPlaying.value = false
        val app = getApplication<Application>()
        val intent = Intent(app, AlarmService::class.java).apply {
            action = AlarmService.ACTION_STOP
        }
        app.startService(intent)
    }

    private fun startLocalCountdown(endTimeIso: String) {
        timerTickJob?.cancel()
        timerTickJob = viewModelScope.launch {
            val sdf = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.US)
            val endMs = try {
                sdf.parse(endTimeIso)?.time
            } catch (_: Exception) {
                try {
                    SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US).parse(endTimeIso)?.time
                } catch (_: Exception) {
                    null
                }
            } ?: return@launch

            while (true) {
                val secondsLeft = ((endMs - System.currentTimeMillis()) / 1000).coerceAtLeast(0)
                _timerSecondsLeft.value = secondsLeft
                if (secondsLeft <= 0) {
                    _timerStatus.value = _timerStatus.value?.copy(active = false)
                    _timerFinished.value = true
                    startAlarmSound()
                    return@launch
                }
                delay(500)
            }
        }
    }

    // ── Settings ───────────────────────────────────────────

    fun updateServerUrl(url: String) {
        prefs.serverUrl = url
        RetrofitClient.init(prefs.serverUrl)
        updateActiveNetworkLabel()
        loadDashboard()
    }

    /**
     * Quick-switch to a specific network URL.
     * Saves, reinitializes Retrofit, tests connection, reloads if connected.
     */
    fun switchNetwork(url: String) {
        viewModelScope.launch {
            _connectionState.value = ConnectionState.CONNECTING
            _connectionStatus.value = "Connecting..."

            prefs.serverUrl = url
            RetrofitClient.init(prefs.serverUrl)
            updateActiveNetworkLabel()

            val ok = RetrofitClient.testConnection()
            if (ok) {
                _connectionState.value = ConnectionState.CONNECTED
                _connectionStatus.value = "✓ Connected"
                loadDashboard()
            } else {
                _connectionState.value = ConnectionState.FAILED
                _connectionStatus.value = "✗ Connection failed"
            }
        }
    }

    fun reconnect() {
        connectWithFallback()
    }

    fun testConnection() {
        viewModelScope.launch {
            _connectionState.value = ConnectionState.CONNECTING
            _connectionStatus.value = "Testing..."
            val ok = RetrofitClient.testConnection()
            _connectionState.value = if (ok) ConnectionState.CONNECTED else ConnectionState.FAILED
            _connectionStatus.value = if (ok) "✓ Connected" else "✗ Failed"
        }
    }

    fun clearConnectionStatus() {
        _connectionStatus.value = null
    }

    fun updateKidsConfig(kids: List<KidConfig>) {
        prefs.kidsConfig = kids
        _kidsConfig.value = kids
    }

    fun updateKidColor(kidId: Int, colorHex: String) {
        val updatedConfigs = _kidsConfig.value.map {
            if (it.id == kidId) it.copy(color = colorHex) else it
        }
        prefs.kidsConfig = updatedConfigs
        _kidsConfig.value = updatedConfigs

        _kids.value = _kids.value.map {
            if (it.id == kidId) it.copy(color = colorHex) else it
        }

        viewModelScope.launch {
            try {
                RetrofitClient.getApiService().updateKid(kidId, UpdateKidColorRequest(colorHex))
            } catch (e: Exception) {
                Log.w("MainViewModel", "Failed to sync kid color to server: ${e.message}")
            }
        }
    }

    // ── Photo Settings ─────────────────────────────────────

    fun loadPhotoSettings() {
        viewModelScope.launch {
            try {
                val response = RetrofitClient.getApiService().getPhotoSettings()
                if (response.isSuccessful) {
                    _photoSettings.value = response.body()
                }
            } catch (_: Exception) {
                Log.w("MainViewModel", "Failed to load photo settings")
            }
        }
    }

    fun updateDisplayMode(mode: String) {
        viewModelScope.launch {
            try {
                val response = RetrofitClient.getApiService().updatePhotoSettings(
                    PhotoSettingsRequest(mode = mode)
                )
                if (response.isSuccessful) {
                    _photoSettings.value = response.body()
                    _photoSettingsSaved.value = "mode"
                    delay(2000)
                    _photoSettingsSaved.value = null
                }
            } catch (_: Exception) {
                _error.value = "Failed to update display mode"
            }
        }
    }

    fun updateAlbumUrl(url: String) {
        viewModelScope.launch {
            try {
                val response = RetrofitClient.getApiService().updatePhotoSettings(
                    PhotoSettingsRequest(album_url = url)
                )
                if (response.isSuccessful) {
                    _photoSettings.value = response.body()
                    _photoSettingsSaved.value = "album"
                    delay(2000)
                    _photoSettingsSaved.value = null
                }
            } catch (_: Exception) {
                _error.value = "Failed to save album URL"
            }
        }
    }

    fun clearError() {
        _error.value = null
    }
}
