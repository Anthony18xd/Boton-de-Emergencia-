package com.emergencia.alertasegura.api

import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.POST

interface ApiService {

    @POST("api/emergencia.php")
    suspend fun enviarEmergencia(@Body body: EmergenciaRequest): Response<EmergenciaResponse>

    companion object {
        // Cambia esta URL por la IP de tu servidor
        private const val BASE_URL = "http://192.168.1.133:8080/"

        fun create(): ApiService {
            return Retrofit.Builder()
                .baseUrl(BASE_URL)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
                .create(ApiService::class.java)
        }
    }
}
