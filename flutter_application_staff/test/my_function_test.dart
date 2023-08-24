import 'package:flutter_application_staff/api/ApiLogin.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('Test sum function', () async {
    dynamic q = ApiLogin();
    dynamic a = await q.login('nguyenminhkhoi@gmail.com', 'Password@123');
    print(a.toString());
  });
}
