package com.emergencia.alertasegura.api

data class EmergenciaRequest(
    val nombre: String,
    val telefono: String,
    val latitud: Double,
    val longitud: Double,
    val tipo_emergencia: String = "general",
    val mensaje: String = "",
    val direccion: String = ""
)

data class EmergenciaResponse(
    val success: Boolean,
    val message: String?,
    val id: Int?,
    val error: String?
)
