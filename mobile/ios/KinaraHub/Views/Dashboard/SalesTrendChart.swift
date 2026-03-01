import SwiftUI
import Charts

struct SalesTrendChart: View {
    let dataPoints: [ChartDataPoint]
    @Binding var selectedPeriod: TrendPeriod
    let onPeriodChange: (TrendPeriod) -> Void
    let isLoading: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Text("Sales Trend")
                    .font(.headline)

                Spacer()

                if isLoading {
                    ProgressView()
                        .scaleEffect(0.7)
                }
            }

            // Period picker
            Picker("Period", selection: $selectedPeriod) {
                ForEach(TrendPeriod.allCases) { period in
                    Text(period.displayName).tag(period)
                }
            }
            .pickerStyle(.segmented)
            .onChange(of: selectedPeriod) { newPeriod in
                onPeriodChange(newPeriod)
            }

            // Chart
            if dataPoints.isEmpty && !isLoading {
                chartEmptyState
            } else {
                chartContent
            }
        }
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(12)
    }

    // MARK: - Chart Content

    private var chartContent: some View {
        Chart(dataPoints) { point in
            LineMark(
                x: .value("Period", point.label),
                y: .value("Amount", point.amount)
            )
            .foregroundStyle(Color.indigo.gradient)
            .interpolationMethod(.catmullRom)
            .lineStyle(StrokeStyle(lineWidth: 2.5))

            AreaMark(
                x: .value("Period", point.label),
                y: .value("Amount", point.amount)
            )
            .foregroundStyle(
                LinearGradient(
                    colors: [Color.indigo.opacity(0.25), Color.indigo.opacity(0.02)],
                    startPoint: .top,
                    endPoint: .bottom
                )
            )
            .interpolationMethod(.catmullRom)

            PointMark(
                x: .value("Period", point.label),
                y: .value("Amount", point.amount)
            )
            .foregroundStyle(Color.indigo)
            .symbolSize(24)
        }
        .chartYAxis {
            AxisMarks(position: .leading) { value in
                AxisGridLine()
                    .foregroundStyle(Color(.systemGray4))
                AxisValueLabel {
                    if let amount = value.as(Double.self) {
                        Text(formatAxisValue(amount))
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                    }
                }
            }
        }
        .chartXAxis {
            AxisMarks { value in
                AxisValueLabel {
                    if let label = value.as(String.self) {
                        Text(label)
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                            .lineLimit(1)
                    }
                }
            }
        }
        .frame(height: 220)
    }

    // MARK: - Empty State

    private var chartEmptyState: some View {
        VStack(spacing: 8) {
            Image(systemName: "chart.line.uptrend.xyaxis")
                .font(.title)
                .foregroundStyle(.secondary)
            Text("No sales data for this period")
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity)
        .frame(height: 220)
    }

    // MARK: - Helpers

    private func formatAxisValue(_ value: Double) -> String {
        if value >= 100_000 {
            return "\(AppConfig.currencySymbol)\(String(format: "%.0fL", value / 100_000))"
        } else if value >= 1_000 {
            return "\(AppConfig.currencySymbol)\(String(format: "%.0fK", value / 1_000))"
        } else {
            return "\(AppConfig.currencySymbol)\(String(format: "%.0f", value))"
        }
    }
}
