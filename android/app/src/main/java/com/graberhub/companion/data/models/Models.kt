package com.graberhub.companion.data.models

import com.google.gson.annotations.SerializedName

data class DashboardResponse(
    val kids: List<Kid>,
    val schedule: List<ScheduleBlock>,
    val countdowns: List<Countdown>,
    val verse: Verse?,
    val date: String
)

data class Kid(
    val id: Int,
    val name: String,
    val color: String,
    val chores: List<Chore> = emptyList()
)

data class Chore(
    val id: Int,
    val kid_id: Int,
    val title: String,
    val done: Boolean,
    val date: String? = null,
    val emoji: String = ""
)

data class ScheduleBlock(
    val id: Int,
    val time_start: String,
    val time_end: String,
    val title: String,
    val emoji: String,
    val date: String? = null,
    val sort: Int = 0
)

data class Countdown(
    val id: Int,
    val label: String,
    val emoji: String,
    val target_date: String
)

data class Verse(
    val id: Int,
    val text: String,
    val reference: String
)

data class TimerStatus(
    val active: Boolean,
    val end_time: String? = null,
    val label: String? = null
)

data class KidConfig(
    val id: Int,
    val name: String,
    val color: String
)

data class ApiResponse(
    val success: Boolean,
    val message: String? = null,
    val id: Int? = null
)

// Request models
data class CreateChoreRequest(
    val kid_id: Int,
    val title: String,
    val date: String? = null,
    val emoji: String = ""
)

data class UpdateChoreRequest(
    val title: String,
    val emoji: String = ""
)

data class CreateScheduleRequest(
    val time_start: String,
    val time_end: String,
    val title: String,
    val emoji: String,
    val date: String? = null,
    val sort: Int? = null
)

data class UpdateScheduleRequest(
    val title: String,
    val emoji: String,
    val time_start: String,
    val time_end: String
)

data class TemplateRequest(
    val blocks: List<CreateScheduleRequest>,
    val date: String? = null
)

data class CreateCountdownRequest(
    val label: String,
    val emoji: String,
    val target_date: String
)

data class CreateTimerRequest(
    val minutes: Int,
    val label: String = ""
)

data class CreateVerseRequest(
    val text: String,
    val reference: String
)

data class UpdateKidColorRequest(
    val color: String
)

// Photo Settings
data class AutoSchedule(
    val photos_start: String? = "20:00",
    val photos_end: String? = "07:00"
)

data class PhotoSettings(
    val mode: String? = "dashboard",
    val album_url: String? = null,
    val transition_seconds: Int? = 10,
    val auto_schedule: AutoSchedule? = null,
    val show_clock: Boolean? = true,
    val show_weather: Boolean? = true,
    val show_date: Boolean? = true,
    val ken_burns: Boolean? = true
)

data class PhotoSettingsRequest(
    val mode: String? = null,
    val album_url: String? = null
)
