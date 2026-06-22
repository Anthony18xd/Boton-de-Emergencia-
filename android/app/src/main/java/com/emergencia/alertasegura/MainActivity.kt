package com.emergencia.alertasegura

import android.Manifest
import android.content.pm.PackageManager
import android.location.Address
import android.location.Geocoder
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Phone
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableDoubleStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.ContextCompat
import com.emergencia.alertasegura.api.ApiService
import com.emergencia.alertasegura.api.EmergenciaRequest
import com.google.android.gms.location.LocationServices
import com.google.android.gms.maps.model.CameraPosition
import com.google.android.gms.maps.model.LatLng
import com.google.maps.android.compose.GoogleMap
import com.google.maps.android.compose.Marker
import com.google.maps.android.compose.MarkerState
import com.google.maps.android.compose.rememberCameraPositionState
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.util.Locale

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            MaterialTheme {
                AlertaSeguraApp()
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AlertaSeguraApp() {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val api = remember { ApiService.create() }

    // Estado de permisos de ubicación
    var hasLocationPermission by remember { mutableStateOf(false) }

    // Ubicación del usuario
    var userLat by remember { mutableDoubleStateOf(0.0) }
    var userLng by remember { mutableDoubleStateOf(0.0) }
    var userAddress by remember { mutableStateOf("") }

    // Estado de la alerta
    var alertSent by remember { mutableStateOf(false) }
    var isLoading by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }

    // Datos del usuario (puedes cambiar por inputs reales)
    val userName = "Ciudadano"
    val userPhone = "999000000"

    // Lanzador de permisos
    val permissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        hasLocationPermission = permissions.values.all { it }
    }

    // Verificar permisos al iniciar
    LaunchedEffect(Unit) {
        hasLocationPermission = ContextCompat.checkSelfPermission(
            context, Manifest.permission.ACCESS_FINE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED

        if (!hasLocationPermission) {
            permissionLauncher.launch(
                arrayOf(
                    Manifest.permission.ACCESS_FINE_LOCATION,
                    Manifest.permission.ACCESS_COARSE_LOCATION
                )
            )
        }
    }

    // Obtener ubicación cuando hay permisos
    LaunchedEffect(hasLocationPermission) {
        if (hasLocationPermission) {
            try {
                val fusedClient = LocationServices.getFusedLocationProviderClient(context)
                fusedClient.lastLocation.addOnSuccessListener { location ->
                    if (location != null) {
                        userLat = location.latitude
                        userLng = location.longitude

                        // Obtener dirección con Geocoder
                        try {
                            val geocoder = Geocoder(context, Locale.getDefault())
                            val addresses: List<Address>? = geocoder.getFromLocation(
                                location.latitude, location.longitude, 1
                            )
                            userAddress = addresses?.first()?.getAddressLine(0) ?: ""
                        } catch (_: Exception) {
                            userAddress = ""
                        }
                    }
                }
            } catch (_: Exception) {}
        }
    }

    // Función para enviar emergencia
    fun enviarEmergencia() {
        if (userLat == 0.0 && userLng == 0.0) {
            errorMessage = "Esperando ubicación del GPS..."
            return
        }

        isLoading = true
        errorMessage = null

        scope.launch(Dispatchers.IO) {
            try {
                val response = api.enviarEmergencia(
                    EmergenciaRequest(
                        nombre = userName,
                        telefono = userPhone,
                        latitud = userLat,
                        longitud = userLng,
                        tipo_emergencia = "general",
                        mensaje = "Alerta de emergencia enviada desde Alerta Segura",
                        direccion = userAddress
                    )
                )

                withContext(Dispatchers.Main) {
                    isLoading = false
                    if (response.isSuccessful && response.body()?.success == true) {
                        alertSent = true
                    } else {
                        errorMessage = response.body()?.error ?: "Error al enviar"
                    }
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    isLoading = false
                    errorMessage = "Error de conexión: ${e.localizedMessage}"
                }
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.LocationOn, contentDescription = null,
                            tint = Color.White)
                        Spacer(Modifier.width(8.dp))
                        Text("Alerta Segura", fontWeight = FontWeight.Bold)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color(0xFFB71C1C),
                    titleContentColor = Color.White
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            if (!hasLocationPermission) {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = Color(0xFFFFF3E0))
                ) {
                    Text(
                        "Se necesita acceso a la ubicación para enviar alertas",
                        modifier = Modifier.padding(16.dp),
                        textAlign = TextAlign.Center,
                        color = Color(0xFFE65100)
                    )
                }
                return@Column
            }

            // Tarjeta de estado
            StatusCard(
                gpsActivo = userLat != 0.0,
                alertaEnviada = alertSent,
                direccion = userAddress
            )

            Spacer(Modifier.height(12.dp))

            // Mapa de Google
            MapCard(
                latitude = if (userLat != 0.0) userLat else -12.062106,
                longitude = if (userLng != 0.0) userLng else -75.235855
            )

            Spacer(Modifier.height(20.dp))

            // Botón SOS
            SOSButton(
                alertSent = alertSent,
                isLoading = isLoading,
                onClick = { enviarEmergencia() }
            )

            Spacer(Modifier.height(16.dp))

            // Mensaje de error
            errorMessage?.let { msg ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = Color(0xFFFFEBEE))
                ) {
                    Text(
                        msg,
                        modifier = Modifier.padding(12.dp),
                        color = Color(0xFFC62828),
                        textAlign = TextAlign.Center,
                        fontSize = 14.sp
                    )
                }
            }

            // Botón de cancelar (solo si alerta activa)
            if (alertSent) {
                Spacer(Modifier.height(12.dp))
                Button(
                    onClick = { alertSent = false },
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color(0xFF757575)
                    ),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("CANCELAR ALERTA", color = Color.White)
                }
            }
        }
    }
}

@Composable
fun StatusCard(gpsActivo: Boolean, alertaEnviada: Boolean, direccion: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                val (icon, text, color) = if (gpsActivo) {
                    Triple("✓", "GPS Activo", Color(0xFF2E7D32))
                } else {
                    Triple("◉", "Buscando GPS...", Color(0xFFF57F17))
                }
                Text(icon, color = color, fontSize = 18.sp)
                Spacer(Modifier.width(8.dp))
                Text(text, color = color, fontWeight = FontWeight.Medium)
            }

            if (direccion.isNotEmpty()) {
                Spacer(Modifier.height(4.dp))
                Text(direccion, fontSize = 12.sp, color = Color.Gray)
            }

            if (alertaEnviada) {
                Spacer(Modifier.height(8.dp))
                Card(
                    colors = CardDefaults.cardColors(containerColor = Color(0xFFE8F5E9))
                ) {
                    Text(
                        "✅ Alerta recibida por la central",
                        modifier = Modifier.padding(8.dp),
                        color = Color(0xFF2E7D32),
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Medium
                    )
                }
            }
        }
    }
}

@Composable
fun MapCard(latitude: Double, longitude: Double) {
    val location = LatLng(latitude, longitude)
    val cameraPositionState = rememberCameraPositionState {
        position = CameraPosition.fromLatLngZoom(location, 16f)
    }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .height(220.dp),
        shape = RoundedCornerShape(24.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
    ) {
        GoogleMap(
            modifier = Modifier.fillMaxSize(),
            cameraPositionState = cameraPositionState
        ) {
            Marker(state = MarkerState(position = location))
        }
    }
}

@Composable
fun SOSButton(
    alertSent: Boolean,
    isLoading: Boolean,
    onClick: () -> Unit
) {
    val infiniteTransition = rememberInfiniteTransition(label = "pulse")
    val pulseScale by infiniteTransition.animateFloat(
        initialValue = 1f,
        targetValue = 1.15f,
        animationSpec = infiniteRepeatable(
            animation = tween(durationMillis = 800, easing = LinearEasing),
        ),
        label = "pulseScale"
    )

    val gradient = Brush.verticalGradient(
        colors = listOf(
            Color(0xFFE53935),
            Color(0xFFB71C1C),
            Color(0xFF8E0000)
        )
    )

    val icon = if (alertSent) Icons.Default.Check else Icons.Default.Phone
    val buttonColor = if (alertSent) Color(0xFF546E7A) else Color.Transparent
    val buttonText = if (alertSent) "ENVIADO" else if (isLoading) "ENVIANDO..." else "S.O.S"

    Box(
        contentAlignment = Alignment.Center,
        modifier = Modifier.size(180.dp)
    ) {
        if (!alertSent && !isLoading) {
            Box(
                modifier = Modifier
                    .size(180.dp)
                    .scale(pulseScale)
                    .background(
                        color = Color(0x33E53935),
                        shape = CircleShape
                    )
            )
        }

        Button(
            onClick = onClick,
            enabled = !isLoading,
            modifier = Modifier
                .size(140.dp),
            shape = CircleShape,
            colors = ButtonDefaults.buttonColors(
                containerColor = if (alertSent) buttonColor else Color.Transparent
            ),
            elevation = ButtonDefaults.buttonElevation(defaultElevation = 12.dp)
        ) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(
                        brush = if (alertSent) Brush.verticalGradient(
                            listOf(Color(0xFF546E7A), Color(0xFF37474F))
                        ) else gradient,
                        shape = CircleShape
                    ),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Icon(
                        imageVector = icon,
                        contentDescription = null,
                        tint = Color.White,
                        modifier = Modifier.size(36.dp)
                    )
                    Text(
                        text = buttonText,
                        color = Color.White,
                        fontWeight = FontWeight.Bold,
                        fontSize = 14.sp
                    )
                }
            }
        }
    }
}
