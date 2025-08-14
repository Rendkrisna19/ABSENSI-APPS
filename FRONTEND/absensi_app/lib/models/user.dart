class User {
  final int id;
  final String name;
  final String email;
  final String role;
  // Tambahkan field lain jika perlu di masa depan
  // final String? nik;
  // final String? position;

  User({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
  });

  // Factory constructor untuk membuat User dari JSON
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      role: json['role'],
    );
  }
}
