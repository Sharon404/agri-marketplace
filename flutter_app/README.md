# Agri Marketplace Flutter App

A Flutter Android application for the Agri Marketplace platform.

## Features

- User authentication (login/register)
- View farmer listings and buyer requests
- Role-based interface (farmer/buyer/admin)
- Real-time marketplace data

## Setup

1. Install Flutter SDK: https://flutter.dev/docs/get-started/install
2. Install Android Studio with Android SDK
3. Set up Android emulator or connect physical device
4. Run `flutter pub get` to install dependencies
5. Update API base URL in `lib/services/api_service.dart` if needed
6. Run `flutter run` to start the app

## API Integration

The app connects to the Laravel backend API running on `http://10.0.2.2:8000/api` (Android emulator localhost).

## Project Structure

```
lib/
├── providers/
│   └── auth_provider.dart          # Authentication state management
├── services/
│   └── api_service.dart            # API communication
├── screens/
│   ├── login_screen.dart           # User login
│   ├── register_screen.dart        # User registration
│   └── home_screen.dart            # Main dashboard
└── main.dart                       # App entry point
```

## Dependencies

- provider: State management
- http: API calls
- shared_preferences: Local storage

## Development Notes

- Android-first development (iOS support can be added later)
- Uses Provider for state management
- JWT tokens stored locally for authentication
- Error handling with user-friendly messages