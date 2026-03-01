// swift-tools-version: 5.9

import PackageDescription

let package = Package(
    name: "KinaraHub",
    platforms: [
        .iOS(.v16)
    ],
    dependencies: [
        .package(url: "https://github.com/kishikawakatsumi/KeychainAccess.git", from: "4.2.2")
    ],
    targets: [
        .executableTarget(
            name: "KinaraHub",
            dependencies: ["KeychainAccess"],
            path: "KinaraHub"
        )
    ]
)
