class CategoryModel {
  CategoryModel({
    required this.id,
    required this.name,
    required this.slug,
    this.parentId,
    this.children = const [],
  });

  final int id;
  final String name;
  final String slug;
  final int? parentId;
  final List<CategoryModel> children;

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: _toInt(json['id']) ?? 0,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      parentId: _toInt(json['parent_id']),
      children: (json['children'] as List<dynamic>? ?? [])
          .map((c) => CategoryModel.fromJson(c as Map<String, dynamic>))
          .toList(),
    );
  }
}

int? _toInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value);
  return null;
}
