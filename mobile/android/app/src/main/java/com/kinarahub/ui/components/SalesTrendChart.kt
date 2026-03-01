package com.kinarahub.ui.components

import android.graphics.Color as AndroidColor
import android.graphics.Typeface
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import com.github.mikephil.charting.charts.LineChart
import com.github.mikephil.charting.components.XAxis
import com.github.mikephil.charting.data.Entry
import com.github.mikephil.charting.data.LineData
import com.github.mikephil.charting.data.LineDataSet
import com.github.mikephil.charting.formatter.IndexAxisValueFormatter
import com.github.mikephil.charting.formatter.ValueFormatter
import com.kinarahub.ui.theme.Primary
import com.kinarahub.ui.theme.PrimaryLight

@Composable
fun SalesTrendChart(
    entries: List<Entry>,
    labels: List<String>,
    selectedPeriod: String,
    onPeriodSelected: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    val primaryColor = Primary.toArgb()
    val primaryLightColor = PrimaryLight.toArgb()
    val surfaceColor = MaterialTheme.colorScheme.surface.toArgb()
    val onSurfaceColor = MaterialTheme.colorScheme.onSurfaceVariant.toArgb()

    Card(
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = "Sales Trend",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold
            )

            Spacer(modifier = Modifier.height(12.dp))

            // Period selector row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                listOf("day", "week", "month", "year").forEach { period ->
                    FilterChip(
                        selected = selectedPeriod == period,
                        onClick = { onPeriodSelected(period) },
                        label = {
                            Text(
                                text = period.replaceFirstChar { it.uppercase() },
                                style = MaterialTheme.typography.labelSmall
                            )
                        }
                    )
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            // MPAndroidChart LineChart via AndroidView
            if (entries.isNotEmpty()) {
                AndroidView(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(220.dp),
                    factory = { context ->
                        LineChart(context).apply {
                            description.isEnabled = false
                            legend.isEnabled = false
                            setDrawGridBackground(false)
                            setTouchEnabled(true)
                            setScaleEnabled(false)
                            setPinchZoom(false)
                            setBackgroundColor(surfaceColor)
                            setNoDataText("No trend data available")
                            setNoDataTextColor(onSurfaceColor)

                            animateX(500)

                            // X axis
                            xAxis.apply {
                                position = XAxis.XAxisPosition.BOTTOM
                                setDrawGridLines(false)
                                granularity = 1f
                                textColor = onSurfaceColor
                                textSize = 10f
                                setLabelRotationAngle(-30f)
                                valueFormatter = IndexAxisValueFormatter(labels)
                            }

                            // Left Y axis
                            axisLeft.apply {
                                setDrawGridLines(true)
                                gridColor = AndroidColor.parseColor("#E2E8F0")
                                gridLineWidth = 0.5f
                                textColor = onSurfaceColor
                                textSize = 10f
                                axisMinimum = 0f
                                valueFormatter = object : ValueFormatter() {
                                    override fun getFormattedValue(value: Float): String {
                                        return when {
                                            value >= 100_000f -> "\u20B9${"%.0f".format(value / 1000)}K"
                                            value >= 1_000f -> "\u20B9${"%.1f".format(value / 1000)}K"
                                            else -> "\u20B9${"%.0f".format(value)}"
                                        }
                                    }
                                }
                            }

                            // Right Y axis disabled
                            axisRight.isEnabled = false

                            // Extra offsets for label clipping
                            setExtraOffsets(8f, 8f, 8f, 16f)
                        }
                    },
                    update = { chart ->
                        val dataSet = LineDataSet(entries, "Sales").apply {
                            color = primaryColor
                            lineWidth = 2.5f
                            setDrawCircles(true)
                            circleRadius = 3.5f
                            setCircleColor(primaryColor)
                            circleHoleColor = AndroidColor.WHITE
                            circleHoleRadius = 2f
                            setDrawValues(false)
                            setDrawFilled(true)
                            fillColor = primaryLightColor
                            fillAlpha = 40
                            mode = LineDataSet.Mode.CUBIC_BEZIER
                            cubicIntensity = 0.15f
                            setDrawHighlightIndicators(true)
                            highLightColor = primaryColor
                            highlightLineWidth = 1f
                        }

                        chart.xAxis.valueFormatter = IndexAxisValueFormatter(labels)
                        chart.data = LineData(dataSet)
                        chart.invalidate()
                    }
                )
            } else {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(220.dp)
                ) {
                    Text(
                        text = "No trend data available",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(32.dp)
                    )
                }
            }
        }
    }
}
