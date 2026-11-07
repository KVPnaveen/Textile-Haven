package springboot_backend.controller;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpSession;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import springboot_backend.dto.ApiResponse;
import springboot_backend.dto.LoginRequest;
import springboot_backend.dto.RegisterRequest;
import springboot_backend.dto.UserDTO;
import springboot_backend.service.AuthService;

@RestController
@RequestMapping("/api/auth")
public class AuthController {

    private final AuthService authService;

    public AuthController(AuthService authService) {
        this.authService = authService;
    }

    @PostMapping("/login")
    public ResponseEntity<ApiResponse<UserDTO>> login(@RequestBody LoginRequest request,
                                                      HttpServletRequest httpRequest) {
        try {
            UserDTO user = authService.login(request);
            HttpSession session = httpRequest.getSession(true);
            session.setAttribute("USER_ID", user.getId());
            session.setAttribute("IS_ADMIN", user.getIsAdmin());

            return ResponseEntity.ok(ApiResponse.ok("Login successful", user));
        } catch (IllegalArgumentException e) {
            return ResponseEntity.badRequest().body(ApiResponse.error(e.getMessage()));
        } catch (Exception e) {
            return ResponseEntity.internalServerError().body(ApiResponse.error("An error occurred during login."));
        }
    }

    @PostMapping("/register")
    public ResponseEntity<ApiResponse<UserDTO>> register(@RequestBody RegisterRequest request) {
        try {
            UserDTO user = authService.register(request);
            return ResponseEntity.ok(ApiResponse.ok("Registration successful", user));
        } catch (IllegalArgumentException e) {
            return ResponseEntity.badRequest().body(ApiResponse.error(e.getMessage()));
        } catch (Exception e) {
            return ResponseEntity.internalServerError().body(ApiResponse.error("Registration failed. Please try again."));
        }
    }

    @GetMapping("/me")
    public ResponseEntity<ApiResponse<UserDTO>> getCurrentUser(
            @RequestHeader(value = "X-User-Id", required = false) Long headerUserId,
            @RequestParam(value = "userId", required = false) Long paramUserId,
            HttpServletRequest request) {

        Long userId = headerUserId != null ? headerUserId : paramUserId;
        if (userId == null) {
            HttpSession session = request.getSession(false);
            if (session != null) {
                userId = (Long) session.getAttribute("USER_ID");
            }
        }

        if (userId == null) {
            return ResponseEntity.ok(new ApiResponse<>(false, "Not authenticated", null));
        }

        UserDTO user = authService.getUserById(userId);
        if (user == null) {
            return ResponseEntity.ok(new ApiResponse<>(false, "User not found", null));
        }

        return ResponseEntity.ok(ApiResponse.ok("Current user", user));
    }

    @PostMapping("/logout")
    public ResponseEntity<ApiResponse<Void>> logout(HttpServletRequest request) {
        HttpSession session = request.getSession(false);
        if (session != null) {
            session.invalidate();
        }
        return ResponseEntity.ok(ApiResponse.ok("Logout successful"));
    }
}
