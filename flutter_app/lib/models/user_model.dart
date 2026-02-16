class UserModel {
  UserModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.phone,
    required this.role,
    required this.status,
    this.sellerProfile,
  });

  final int id;
  final String firstName;
  final String lastName;
  final String email;
  final String phone;
  final String role;
  final String status;
  final SellerModel? sellerProfile;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      firstName: json['first_name'] as String? ?? '',
      lastName: json['last_name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      phone: json['phone'] as String? ?? '',
      role: json['role'] as String? ?? 'buyer',
      status: json['status'] as String? ?? 'active',
      sellerProfile: json['seller_profile'] != null
          ? SellerModel.fromJson(json['seller_profile'] as Map<String, dynamic>)
          : null,
    );
  }
}

class SellerModel {
  SellerModel({
    required this.id,
    required this.userId,
    required this.businessName,
    required this.description,
    required this.logoUrl,
    required this.verificationStatus,
    required this.commissionRate,
  });

  final int id;
  final int userId;
  final String businessName;
  final String? description;
  final String? logoUrl;
  final String verificationStatus;
  final double? commissionRate;

  factory SellerModel.fromJson(Map<String, dynamic> json) {
    return SellerModel(
      id: json['id'] as int,
      userId: json['user_id'] as int,
      businessName: json['business_name'] as String? ?? '',
      description: json['description'] as String?,
      logoUrl: json['logo_url'] as String?,
      verificationStatus: json['verification_status'] as String? ?? 'pending',
      commissionRate: (json['commission_rate'] as num?)?.toDouble(),
    );
  }
}
