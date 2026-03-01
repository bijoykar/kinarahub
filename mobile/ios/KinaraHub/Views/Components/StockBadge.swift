import SwiftUI

struct StockBadge: View {
    let status: StockStatus

    var label: String {
        switch status {
        case .inStock: return "In Stock"
        case .lowStock: return "Low Stock"
        case .outOfStock: return "Out of Stock"
        }
    }

    var color: Color {
        switch status {
        case .inStock: return .green
        case .lowStock: return .orange
        case .outOfStock: return .red
        }
    }

    var icon: String {
        switch status {
        case .inStock: return "checkmark.circle.fill"
        case .lowStock: return "exclamationmark.circle.fill"
        case .outOfStock: return "xmark.circle.fill"
        }
    }

    var body: some View {
        HStack(spacing: 4) {
            Image(systemName: icon)
                .font(.caption2)
            Text(label)
                .font(.caption2)
                .fontWeight(.medium)
        }
        .padding(.horizontal, 8)
        .padding(.vertical, 4)
        .background(color.opacity(0.15))
        .foregroundStyle(color)
        .cornerRadius(6)
    }
}
